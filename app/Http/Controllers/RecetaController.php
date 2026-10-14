<?php

namespace App\Http\Controllers;

use App\Models\CategoriaMenu;
use App\Models\GrupoModificador;
use App\Models\Insumo;
use App\Models\Modificador;
use App\Models\ModificadorInsumo;
use App\Models\Producto;
use App\Models\RecetaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Recetas: qué insumos consume cada producto (y cada variante) y cada opción del menú.
 * Con esto, cada venta descuenta los insumos de la sede donde se vendió.
 */
class RecetaController extends Controller
{
    public function index()
    {
        $this->soloPropietario();

        $categorias = CategoriaMenu::orderBy('orden')
            ->with([
                'productos' => fn ($q) => $q->orderBy('orden_menu')->orderBy('nombre'),
                'productos.variantes',
                'productos.recetaItems',
            ])
            ->get();

        $productos = $categorias->map(fn ($c) => [
            'categoria' => $c->nombre,
            'productos' => $c->productos->map(fn ($p) => [
                'id'        => $p->id,
                'nombre'    => $p->nombre,
                'tipo'      => $p->tipo_menu,
                'activo'    => (bool) $p->activo,
                'precio'    => (int) $p->precio_venta,
                'variantes' => $p->variantes->map(fn ($v) => [
                    'id' => $v->id, 'nombre' => $v->nombre, 'precio' => (int) $v->precio,
                ])->values(),
                'receta'    => $p->recetaItems->map(fn ($r) => [
                    'insumo_id'   => $r->insumo_id,
                    'variante_id' => $r->variante_id,
                    'cantidad'    => (float) $r->cantidad,
                ])->values(),
            ])->values(),
        ])->filter(fn ($c) => $c['productos']->isNotEmpty())->values();

        $grupos = GrupoModificador::orderBy('orden')
            ->with(['modificadores' => fn ($q) => $q->orderBy('orden')->orderBy('nombre'), 'modificadores.insumos'])
            ->get()
            ->map(fn ($g) => [
                'id'       => $g->id,
                'nombre'   => $g->nombre,
                'opciones' => $g->modificadores->map(fn ($m) => [
                    'id'      => $m->id,
                    'nombre'  => $m->nombre,
                    'activo'  => (bool) $m->activo,
                    'insumos' => $m->insumos->map(fn ($mi) => [
                        'insumo_id' => $mi->insumo_id,
                        'cantidad'  => (float) $mi->cantidad,
                    ])->values(),
                ])->values(),
            ])->values();

        $insumos = Insumo::orderBy('nombre')->get()->map(fn ($i) => [
            'id'     => $i->id,
            'nombre' => $i->nombre,
            'unidad' => $i->unidad,
            'costo'  => (float) $i->costo_unitario,
            'activo' => (bool) $i->activo,
        ])->values();

        $categoriasInsumo = InsumoController::CATEGORIAS;

        return view('menu.recetas', compact('productos', 'grupos', 'insumos', 'categoriasInsumo'));
    }

    // Reemplaza la receta completa de un producto
    public function guardarProducto(Request $request, Producto $producto)
    {
        $this->soloPropietario();

        $tenantId = (int) session('tenant_id');

        $data = $request->validate([
            'lineas'               => 'present|array|max:40',
            'lineas.*.insumo_id'   => ['required', 'integer', Rule::exists('insumos', 'id')->where('tenant_id', $tenantId)],
            'lineas.*.variante_id' => ['nullable', 'integer', Rule::exists('producto_variantes', 'id')->where('producto_id', $producto->id)],
            'lineas.*.cantidad'    => 'required|numeric|gt:0|max:100000',
        ], [
            'lineas.*.insumo_id.required' => 'Elige el insumo en cada línea.',
            'lineas.*.cantidad.gt'        => 'Cada cantidad debe ser mayor que cero.',
            'lineas.*.cantidad.required'  => 'Escribe la cantidad en cada línea.',
        ]);

        // Agrupar líneas repetidas (mismo insumo y misma variante)
        $lineas = collect($data['lineas'])
            ->groupBy(fn ($l) => $l['insumo_id'] . '|' . ($l['variante_id'] ?? ''))
            ->map(fn ($g) => [
                'insumo_id'   => (int) $g->first()['insumo_id'],
                'variante_id' => isset($g->first()['variante_id']) ? (int) $g->first()['variante_id'] : null,
                'cantidad'    => round($g->sum(fn ($l) => (float) $l['cantidad']), 3),
            ])->values();

        DB::transaction(function () use ($producto, $lineas) {
            RecetaItem::where('producto_id', $producto->id)->delete();

            foreach ($lineas as $l) {
                RecetaItem::create(['producto_id' => $producto->id] + $l);
            }
        });

        return response()->json([
            'success' => true,
            'mensaje' => $lineas->isEmpty()
                ? "{$producto->nombre} quedó sin receta (no descuenta insumos)."
                : "Receta de {$producto->nombre} guardada.",
        ]);
    }

    // Reemplaza los insumos que consume una opción (sabor, fruta, topping, salsa...)
    public function guardarOpcion(Request $request, Modificador $modificador)
    {
        $this->soloPropietario();

        // El grupo se consulta con TenantScope: si es de otro negocio, llega vacío
        if (!$modificador->grupo()->exists()) {
            abort(403, 'No autorizado');
        }

        $tenantId = (int) session('tenant_id');

        $data = $request->validate([
            'lineas'             => 'present|array|max:20',
            'lineas.*.insumo_id' => ['required', 'integer', Rule::exists('insumos', 'id')->where('tenant_id', $tenantId)],
            'lineas.*.cantidad'  => 'required|numeric|gt:0|max:100000',
        ], [
            'lineas.*.insumo_id.required' => 'Elige el insumo en cada línea.',
            'lineas.*.cantidad.gt'        => 'Cada cantidad debe ser mayor que cero.',
            'lineas.*.cantidad.required'  => 'Escribe la cantidad en cada línea.',
        ]);

        $lineas = collect($data['lineas'])
            ->groupBy('insumo_id')
            ->map(fn ($g, $insumoId) => [
                'insumo_id' => (int) $insumoId,
                'cantidad'  => round($g->sum(fn ($l) => (float) $l['cantidad']), 3),
            ])->values();

        DB::transaction(function () use ($modificador, $lineas) {
            ModificadorInsumo::where('modificador_id', $modificador->id)->delete();

            foreach ($lineas as $l) {
                ModificadorInsumo::create(['modificador_id' => $modificador->id] + $l);
            }
        });

        return response()->json([
            'success' => true,
            'mensaje' => $lineas->isEmpty()
                ? "{$modificador->nombre} quedó sin insumos."
                : "Insumos de {$modificador->nombre} guardados.",
        ]);
    }

    private function soloPropietario(): void
    {
        if (!auth()->user()?->esPropietario()) {
            abort(403, 'Solo el propietario puede administrar las recetas');
        }
    }
}
