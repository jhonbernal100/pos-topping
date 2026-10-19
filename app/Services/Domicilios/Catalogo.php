<?php

namespace App\Services\Domicilios;

use App\Models\CategoriaMenu;
use App\Models\Scopes\TenantScope;

/**
 * Menú para el pedido web a domicilio (misma estructura que la caja).
 * La disponibilidad final y los precios los valida CalculadoraPedido al enviar.
 */
class Catalogo
{
    public function domicilio(int $tenantId): array
    {
        return CategoriaMenu::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('activo', true)
            ->orderBy('orden')
            ->with([
                'productos' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->where('activo', true)
                    ->orderBy('orden_menu')->orderBy('nombre'),
                'productos.variantesActivas',
                'productos.gruposModificadores.modificadores',
            ])
            ->get()
            ->map(fn ($c) => [
                'id'        => $c->id,
                'nombre'    => $c->nombre,
                'productos' => $c->productos
                    ->filter(fn ($p) => $p->disponiblePara('domicilio'))
                    ->map(fn ($p) => [
                        'id'          => $p->id,
                        'nombre'      => $p->nombre,
                        'descripcion' => $p->descripcion,
                        'foto'        => $p->foto ? asset('storage/' . $p->foto) : null,
                        'precio'      => (int) $p->precio_venta,
                        'variantes'   => $p->variantesActivas->map(fn ($v) => ['id' => $v->id, 'nombre' => $v->nombre, 'precio' => (int) $v->precio])->values(),
                        'grupos'      => $p->gruposModificadores->map(fn ($g) => [
                            'id'        => $g->id,
                            'nombre'    => $g->nombre,
                            'incluidos' => (int) $g->pivot->incluidos,
                            'minimo'    => (int) $g->pivot->minimo,
                            'maximo'    => $g->pivot->maximo !== null ? (int) $g->pivot->maximo : null,
                            'precio'    => (int) ($g->pivot->precio_extra ?? $g->precio_extra),
                            'opciones'  => $g->modificadores->filter(fn ($m) => $m->disponibleHoy())
                                ->map(fn ($m) => ['id' => $m->id, 'nombre' => $m->nombre, 'precio' => $m->precio_extra])->values(),
                        ])->values(),
                    ])->values(),
            ])
            ->filter(fn ($c) => $c['productos']->isNotEmpty())
            ->values()
            ->all();
    }
}
