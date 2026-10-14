<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\InsumoStock;
use App\Models\MovimientoInventario;
use App\Models\Sede;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InsumoController extends Controller
{
    public const CATEGORIAS = ['Helados', 'Frutas', 'Toppings', 'Salsas', 'Bases y postres', 'Bebidas', 'Empaques', 'Otros'];

    // Lista con existencias de la sede activa
    public function index()
    {
        $sedeId = (int) session('sede_id');
        $sede   = Sede::find($sedeId);

        $insumos = Insumo::with(['stocks' => fn ($q) => $q->where('sede_id', $sedeId)])
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->map(function ($i) {
                $stock = $i->stocks->first();
                $i->stock_actual = (float) ($stock->cantidad ?? 0);
                $i->stock_min    = (float) ($stock->stock_minimo ?? 0);
                $i->con_conteo   = (bool) $stock;
                $i->estado       = $stock ? $stock->estado() : 'sin_conteo';
                return $i;
            });

        $alertas = $insumos->whereIn('estado', ['agotado', 'bajo'])->where('activo', true)->count();
        $valor   = $insumos->sum(fn ($i) => max(0, $i->stock_actual) * $i->costo_unitario);

        return view('insumos.index', compact('sede', 'insumos', 'alertas', 'valor'));
    }

    public function crear()
    {
        $this->soloGerente();

        return view('insumos.form', [
            'insumo'     => new Insumo(['unidad' => 'g', 'activo' => true]),
            'stockMin'   => 0,
            'categorias' => self::CATEGORIAS,
        ]);
    }

    public function store(Request $request)
    {
        $this->soloGerente();
        $data = $this->validar($request);

        $insumo = Insumo::create([
            'tenant_id' => (int) session('tenant_id'),
            'nombre'    => $data['nombre'],
            'unidad'    => $data['unidad'],
            'categoria' => $data['categoria'] ?? null,
            'activo'    => true,
        ]);

        $this->guardarMinimo($insumo, (float) ($data['stock_minimo'] ?? 0));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'insumo'  => ['id' => $insumo->id, 'nombre' => $insumo->nombre, 'unidad' => $insumo->unidad],
            ]);
        }

        return redirect('/insumos')->with('ok', "Insumo {$insumo->nombre} creado.");
    }

    public function editar(Insumo $insumo)
    {
        $this->soloGerente();

        $stockMin = (float) InsumoStock::where('insumo_id', $insumo->id)
            ->where('sede_id', (int) session('sede_id'))
            ->value('stock_minimo');

        return view('insumos.form', [
            'insumo'     => $insumo,
            'stockMin'   => $stockMin,
            'categorias' => self::CATEGORIAS,
        ]);
    }

    public function actualizar(Request $request, Insumo $insumo)
    {
        $this->soloGerente();
        $data = $this->validar($request, $insumo);

        // La unidad no se puede cambiar si ya hay movimientos (descuadraría el kardex)
        if ($data['unidad'] !== $insumo->unidad && MovimientoInventario::where('insumo_id', $insumo->id)->exists()) {
            return back()->withInput()->withErrors(['unidad' => 'No se puede cambiar la unidad de un insumo que ya tiene movimientos.']);
        }

        $insumo->update([
            'nombre'    => $data['nombre'],
            'unidad'    => $data['unidad'],
            'categoria' => $data['categoria'] ?? null,
            'activo'    => $request->boolean('activo'),
        ]);

        $this->guardarMinimo($insumo, (float) ($data['stock_minimo'] ?? 0));

        return redirect('/insumos')->with('ok', "Insumo {$insumo->nombre} actualizado.");
    }

    // Conteo físico (gerentes) o merma (todos)
    public function ajuste(Request $request, Insumo $insumo, InventarioService $inventario)
    {
        $data = $request->validate([
            'tipo'     => 'required|in:conteo,merma',
            'cantidad' => 'required|numeric|min:0|max:100000000',
            'nota'     => 'nullable|string|max:191',
        ]);

        if ($data['tipo'] === 'conteo') {
            $this->soloGerente();
        }

        $sedeId = (int) session('sede_id');
        if (!$sedeId) {
            return response()->json(['success' => false, 'mensaje' => 'No hay una sede activa.'], 422);
        }

        $mov = DB::transaction(function () use ($data, $insumo, $sedeId, $inventario) {
            if ($data['tipo'] === 'merma') {
                if ((float) $data['cantidad'] <= 0) {
                    return null;
                }
                return $inventario->registrar($sedeId, $insumo->id, -(float) $data['cantidad'], 'merma', [
                    'nota' => $data['nota'] ?: 'Merma',
                ]);
            }

            // Conteo: la diferencia entre lo contado y lo que dice el sistema
            $actual = (float) InsumoStock::where('insumo_id', $insumo->id)->where('sede_id', $sedeId)
                ->lockForUpdate()->value('cantidad');
            $diferencia = round((float) $data['cantidad'] - $actual, 3);

            return $inventario->registrar($sedeId, $insumo->id, $diferencia, 'ajuste', [
                'nota' => $data['nota'] ?: 'Conteo físico',
            ]);
        });

        if (!$mov) {
            return response()->json(['success' => false, 'mensaje' => 'La merma debe ser mayor que cero.'], 422);
        }

        return response()->json([
            'success' => true,
            'saldo'   => $mov->saldo,
            'texto'   => Insumo::formatear($mov->saldo, $insumo->unidad),
            'mensaje' => $data['tipo'] === 'merma'
                ? 'Merma registrada. Queda ' . Insumo::formatear($mov->saldo, $insumo->unidad) . '.'
                : 'Conteo guardado: ' . Insumo::formatear($mov->saldo, $insumo->unidad) . '.',
        ]);
    }

    // Kardex de la sede activa
    public function movimientos(Insumo $insumo)
    {
        $sede = Sede::find((int) session('sede_id'));

        $movimientos = MovimientoInventario::with(['usuario', 'venta', 'compra'])
            ->where('insumo_id', $insumo->id)
            ->where('sede_id', (int) session('sede_id'))
            ->orderByDesc('id')
            ->paginate(40);

        return view('insumos.movimientos', compact('insumo', 'sede', 'movimientos'));
    }

    // ---------------------------------------------------------------

    private function validar(Request $request, ?Insumo $insumo = null): array
    {
        $tenantId = (int) session('tenant_id');

        return $request->validate([
            'nombre'       => [
                'required', 'string', 'max:120',
                Rule::unique('insumos', 'nombre')->where('tenant_id', $tenantId)->ignore($insumo?->id),
            ],
            'unidad'       => 'required|in:g,ml,und',
            'categoria'    => 'nullable|string|max:60',
            'stock_minimo' => 'nullable|numeric|min:0|max:100000000',
        ], [
            'nombre.unique' => 'Ya existe un insumo con ese nombre.',
        ]);
    }

    private function guardarMinimo(Insumo $insumo, float $minimo): void
    {
        $sedeId = (int) session('sede_id');
        if (!$sedeId) {
            return;
        }

        $stock = InsumoStock::where('insumo_id', $insumo->id)->where('sede_id', $sedeId)->first();

        // Sin conteo y sin mínimo: no se crea la fila (el insumo queda "sin conteo", sin alertas)
        if (!$stock && $minimo <= 0) {
            return;
        }

        $stock ??= InsumoStock::create(['insumo_id' => $insumo->id, 'sede_id' => $sedeId, 'cantidad' => 0]);
        $stock->update(['stock_minimo' => $minimo]);
    }

    private function soloGerente(): void
    {
        if (auth()->user()->rol !== 'dueno') {
            abort(403, 'Solo un gerente o el propietario puede hacer esto.');
        }
    }
}
