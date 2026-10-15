<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Insumo;
use App\Models\InsumoAlias;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Services\InventarioService;
use App\Services\LectorFacturaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CompraController extends Controller
{
    public function index()
    {
        $this->soloGerente();

        $sedeId = (int) session('sede_id');
        $sede   = Sede::find($sedeId);

        $compras = Compra::with(['proveedor', 'usuario'])->withCount('items')
            ->where('sede_id', $sedeId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        $totalMes = (int) Compra::where('sede_id', $sedeId)
            ->where('estado', 'registrada')
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month)
            ->sum('total');

        return view('compras.index', compact('sede', 'compras', 'totalMes'));
    }

    public function crear()
    {
        $this->soloGerente();

        $sede        = Sede::find((int) session('sede_id'));
        $proveedores = $this->proveedores();
        $insumos     = Insumo::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'unidad', 'categoria']);
        $categorias  = InsumoController::CATEGORIAS;

        return view('compras.crear', compact('sede', 'proveedores', 'insumos', 'categorias'));
    }

    // Lee una factura (PDF o foto) con IA y devuelve los datos para llenar el formulario
    public function leer(Request $request, LectorFacturaService $lector)
    {
        $this->soloGerente();

        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ], [
            'archivo.required' => 'Elige el PDF o la foto de la factura.',
            'archivo.mimes'    => 'Sube un PDF o una foto (JPG, PNG o WEBP).',
            'archivo.max'      => 'El archivo pesa más de 10 MB.',
        ]);

        $archivo = $request->file('archivo');

        try {
            $lectura = $lector->leer($archivo);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage());
        }

        // Guardar el archivo y la lectura mientras se confirma la compra
        $ext   = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());
        $token = Str::uuid() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $dir   = $this->dirTemporal();

        Storage::disk('local')->putFileAs($dir, $archivo, $token);
        Storage::disk('local')->put($dir . '/' . $token . '.json', json_encode($lectura, JSON_UNESCAPED_UNICODE));

        return response()->json([
            'success' => true,
            'token'   => $token,
            'datos'   => $lector->preparar($lectura),
        ]);
    }

    // Ver el PDF o la foto original de una compra leída con IA
    public function archivo(Compra $compra)
    {
        $this->soloGerente();
        $this->verificarSede($compra);

        if (!$compra->foto || !Storage::disk('local')->exists($compra->foto)) {
            abort(404);
        }

        return Storage::disk('local')->response($compra->foto);
    }

    public function store(Request $request, InventarioService $inventario)
    {
        $this->soloGerente();

        $tenantId = (int) session('tenant_id');
        $sedeId   = (int) session('sede_id');

        if (!$sedeId) {
            return $this->error('No hay una sede activa.');
        }

        $data = $request->validate([
            'proveedor_id'             => ['nullable', 'integer', Rule::in($this->proveedores()->pluck('id')->all())],
            'proveedor_nuevo.nombre'   => 'nullable|string|max:191',
            'proveedor_nuevo.nit'      => 'nullable|string|max:20',
            'impuestos_al_costo'       => 'nullable|boolean',
            'total_factura'            => 'nullable|integer|min:0',
            'archivo_token'            => ['nullable', 'string', 'regex:/^[0-9a-f-]{36}\.(pdf|jpg|png|webp)$/'],
            'numero_factura'           => 'nullable|string|max:60',
            'fecha'                    => 'required|date|before_or_equal:today',
            'impuestos'                => 'nullable|integer|min:0',
            'notas'                    => 'nullable|string|max:500',
            'items'                    => 'required|array|min:1|max:80',
            // insumo_id vacío = no es inventario (domicilio, flete, servicio)
            'items.*.insumo_id'        => ['nullable', 'integer', Rule::exists('insumos', 'id')->where('tenant_id', $tenantId)],
            'items.*.descripcion'      => 'nullable|required_without:items.*.insumo_id|string|max:191',
            'items.*.cantidad_factura' => 'required|numeric|gt:0|max:1000000',
            'items.*.unidad_factura'   => 'nullable|string|max:30',
            'items.*.factor'           => 'required|numeric|gt:0|max:1000000',
            'items.*.costo_total'      => 'required|integer|min:0|max:1000000000',
        ], [
            'items.required'          => 'Agrega al menos un insumo a la factura.',
            'items.*.descripcion.required_without' => 'Escribe la descripción de las líneas que no son inventario.',
            'fecha.before_or_equal'   => 'La fecha de la factura no puede ser futura.',
        ]);

        // Evitar registrar dos veces la misma factura del mismo proveedor
        if (!empty($data['numero_factura']) && !empty($data['proveedor_id'])) {
            $repetida = Compra::where('proveedor_id', $data['proveedor_id'])
                ->where('numero_factura', trim($data['numero_factura']))
                ->where('estado', 'registrada')
                ->exists();

            if ($repetida) {
                return $this->error('Esa factura de ese proveedor ya fue registrada.');
            }
        }

        $insumos  = Insumo::whereIn('id', collect($data['items'])->pluck('insumo_id')->filter())->get()->keyBy('id');

        // Archivo leído con IA (queda guardado con la compra)
        $token   = $data['archivo_token'] ?? null;
        $dirTmp  = $this->dirTemporal();
        $lectura = null;
        if ($token) {
            if (!Storage::disk('local')->exists($dirTmp . '/' . $token)) {
                return $this->error('El archivo de la factura ya no está disponible. Vuelve a leerla.');
            }
            $lectura = json_decode((string) Storage::disk('local')->get($dirTmp . '/' . $token . '.json'), true);
        }
        $subtotal = (int) collect($data['items'])->sum('costo_total');
        $impuesto = (int) ($data['impuestos'] ?? 0);

        try {
            $compra = DB::transaction(function () use ($data, $tenantId, $sedeId, $insumos, $subtotal, $impuesto, $inventario, $token, $lectura) {
                // Proveedor nuevo leído de la factura
                if (empty($data['proveedor_id']) && !empty(trim($data['proveedor_nuevo']['nombre'] ?? ''))) {
                    $data['proveedor_id'] = $this->crearProveedor(
                        $tenantId,
                        trim($data['proveedor_nuevo']['nombre']),
                        trim($data['proveedor_nuevo']['nit'] ?? '') ?: null
                    );

                    if (!empty($data['numero_factura']) && Compra::where('proveedor_id', $data['proveedor_id'])
                            ->where('numero_factura', trim($data['numero_factura']))->where('estado', 'registrada')->exists()) {
                        throw new InvalidArgumentException('Esa factura de ese proveedor ya fue registrada.');
                    }
                }

                $compra = Compra::create([
                    'tenant_id'      => $tenantId,
                    'sede_id'        => $sedeId,
                    'proveedor_id'   => $data['proveedor_id'] ?? null,
                    'user_id'        => auth()->id(),
                    'numero_factura' => isset($data['numero_factura']) ? trim($data['numero_factura']) ?: null : null,
                    'fecha'          => $data['fecha'],
                    'subtotal'       => $subtotal,
                    'impuestos'      => $impuesto,
                    'impuestos_al_costo' => (bool) ($data['impuestos_al_costo'] ?? false),
                    'total'          => $subtotal + $impuesto,
                    'total_factura'  => $data['total_factura'] ?? null,
                    'estado'         => 'registrada',
                    'origen'         => $token ? 'ia' : 'manual',
                    'lectura_ia'     => $lectura,
                    'notas'          => $data['notas'] ?? null,
                ]);

                foreach ($data['items'] as $it) {
                    $insumo      = !empty($it['insumo_id']) ? $insumos[$it['insumo_id']] : null;
                    $descripcion = trim($it['descripcion'] ?? '') ?: $insumo->nombre;

                    CompraItem::create([
                        'compra_id'        => $compra->id,
                        'insumo_id'        => $insumo?->id,
                        'descripcion'      => $descripcion,
                        'cantidad_factura' => (float) $it['cantidad_factura'],
                        'unidad_factura'   => $it['unidad_factura'] ?? null,
                        'factor'           => (float) $it['factor'],
                        'cantidad'         => round((float) $it['cantidad_factura'] * (float) $it['factor'], 3),
                        'costo_total'      => (int) $it['costo_total'],
                    ]);

                    // Aprender cómo llama el proveedor a este insumo (lo usará la lectura con IA)
                    if ($insumo && !empty($it['descripcion'])) {
                        InsumoAlias::updateOrCreate(
                            [
                                'tenant_id'    => $tenantId,
                                'proveedor_id' => $data['proveedor_id'] ?? null,
                                'texto'        => InsumoAlias::normalizar($it['descripcion']),
                            ],
                            ['insumo_id' => $insumo->id, 'factor' => (float) $it['factor']]
                        );
                    }
                }

                // Guardar el PDF o la foto junto a la compra
                if ($token) {
                    $destino = 'compras/' . $tenantId . '/' . $compra->id . '-' . $token;
                    Storage::disk('local')->move($this->dirTemporal() . '/' . $token, $destino);
                    Storage::disk('local')->delete($this->dirTemporal() . '/' . $token . '.json');
                    $compra->update(['foto' => $destino]);
                }

                $compra->load('proveedor');
                $inventario->registrarCompra($compra);

                return $compra;
            });
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }

        return response()->json([
            'success' => true,
            'id'      => $compra->id,
            'mensaje' => 'Factura registrada y existencias actualizadas.',
        ]);
    }

    public function ver(Compra $compra)
    {
        $this->soloGerente();
        $this->verificarSede($compra);

        $compra->load(['items.insumo', 'proveedor', 'usuario', 'sede']);

        return view('compras.ver', compact('compra'));
    }

    public function anular(Compra $compra, InventarioService $inventario)
    {
        $this->soloGerente();
        $this->verificarSede($compra);

        if ($compra->estado !== 'registrada') {
            return $this->error('Esta factura no se puede anular.');
        }

        DB::transaction(function () use ($compra, $inventario) {
            foreach ($compra->items()->whereNotNull('insumo_id')->orderBy('insumo_id')->get() as $item) {
                if ($item->cantidad > 0) {
                    $inventario->registrar((int) $compra->sede_id, (int) $item->insumo_id, -$item->cantidad, 'ajuste', [
                        'compra_id' => $compra->id,
                        'nota'      => 'Anulación factura ' . ($compra->numero_factura ?? '#' . $compra->id),
                    ]);
                }
            }

            $compra->update(['estado' => 'anulada']);
        });

        return response()->json(['success' => true, 'mensaje' => 'Factura anulada y existencias descontadas.']);
    }

    // ---------------------------------------------------------------

    private function dirTemporal(): string
    {
        return 'compras/tmp/' . (int) session('tenant_id');
    }

    // Busca el proveedor por NIT antes de crearlo, para no duplicarlo
    private function crearProveedor(int $tenantId, string $nombre, ?string $nit): int
    {
        if ($nit) {
            $digitos = preg_replace('/\D/', '', $nit);
            $existente = Proveedor::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->get(['id', 'nit'])
                ->first(fn ($p) => $digitos !== '' && preg_replace('/\D/', '', (string) $p->nit) === $digitos);
            if ($existente) {
                return $existente->id;
            }
        }

        return Proveedor::create([
            'tenant_id' => $tenantId,
            'nombre'    => mb_substr($nombre, 0, 191),
            'nit'       => $nit ? mb_substr($nit, 0, 20) : null,
            'activo'    => true,
        ])->id;
    }

    // Proveedores del negocio (tolera la tabla heredada del ferretero con o sin "activo")
    private function proveedores()
    {
        $q = Proveedor::query()->withoutGlobalScopes();

        if (Schema::hasColumn('proveedores', 'tenant_id')) {
            $q->where('tenant_id', (int) session('tenant_id'));
        }
        if (Schema::hasColumn('proveedores', 'activo')) {
            $q->where('activo', true);
        }

        return $q->orderBy('nombre')->get(['id', 'nombre']);
    }

    private function verificarSede(Compra $compra): void
    {
        $permitidas = array_map('intval', auth()->user()->sedesPermitidasIds());
        if (!in_array((int) $compra->sede_id, $permitidas, true)) {
            abort(403, 'No autorizado');
        }
    }

    private function soloGerente(): void
    {
        if (auth()->user()->rol !== 'dueno') {
            abort(403, 'Solo un gerente o el propietario puede registrar compras.');
        }
    }

    private function error(string $mensaje, int $status = 422)
    {
        return response()->json(['success' => false, 'mensaje' => $mensaje], $status);
    }
}
