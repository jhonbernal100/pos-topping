<?php

namespace App\Http\Controllers;

use App\Models\CategoriaMenu;
use App\Models\GrupoModificador;
use App\Models\Modificador;
use App\Models\Producto;

class MenuController extends Controller
{
    public function index()
    {
        $this->soloPropietario();

        $categorias = CategoriaMenu::orderBy('orden')
            ->with([
                'productos' => fn ($q) => $q->orderBy('orden_menu')->orderBy('nombre'),
                'productos.variantes',
                'productos.gruposModificadores',
            ])
            ->get();

        $grupos = GrupoModificador::orderBy('orden')
            ->with('modificadores')
            ->get();

        return view('menu.index', compact('categorias', 'grupos'));
    }

    public function toggleProducto(Producto $producto)
    {
        $this->soloPropietario();

        $producto->update(['activo' => !$producto->activo]);

        return response()->json([
            'success' => true,
            'activo'  => $producto->activo,
            'mensaje' => $producto->nombre . ($producto->activo ? ' disponible' : ' marcado como no disponible'),
        ]);
    }

    public function toggleModificador(Modificador $modificador)
    {
        $this->soloPropietario();

        // El grupo se consulta con TenantScope: si es de otro negocio, llega vacío
        if (!$modificador->grupo()->exists()) {
            abort(403, 'No autorizado');
        }

        $modificador->update(['activo' => !$modificador->activo]);

        return response()->json([
            'success' => true,
            'activo'  => $modificador->activo,
            'mensaje' => $modificador->nombre . ($modificador->activo ? ' disponible' : ' marcado como agotado'),
        ]);
    }

    private function soloPropietario(): void
    {
        if (!auth()->user()?->esPropietario()) {
            abort(403, 'Solo el propietario puede administrar el menú');
        }
    }
}