<?php

namespace App\Services;

use App\Models\Producto;
use InvalidArgumentException;

/**
 * Calcula el precio de un producto del menú con su variante y opciones.
 * El precio SIEMPRE se calcula aquí, nunca se toma del navegador.
 *
 * $item = [
 *   'producto_id'   => 5,
 *   'variante_id'   => 12,              // obligatorio si el producto tiene variantes
 *   'cantidad'      => 1,
 *   'modificadores' => [3, 3, 8, 15],   // ids; repetir un id = elegirlo varias veces
 *   'notas'         => 'sin salsa',
 * ]
 */
class CalculadoraPedido
{
    public function calcularItem(array $item, string $canal = 'local'): array
    {
        $producto = Producto::with(['variantesActivas', 'gruposModificadores.modificadores'])
            ->find((int) ($item['producto_id'] ?? 0));

        if (!$producto) {
            throw new InvalidArgumentException('Uno de los productos ya no existe en el menú.');
        }

        if (!$producto->disponiblePara($canal)) {
            throw new InvalidArgumentException("{$producto->nombre} no está disponible hoy.");
        }

        // Variante
        $variante = null;
        if ($producto->variantesActivas->isNotEmpty()) {
            $variante = $producto->variantesActivas->firstWhere('id', (int) ($item['variante_id'] ?? 0));
            if (!$variante) {
                throw new InvalidArgumentException("Elige una opción de {$producto->nombre}.");
            }
        }

        $precioBase = $variante ? (int) $variante->precio : (int) $producto->precio_venta;

        // Mapa de opciones permitidas: id => [modificador, grupo]
        $permitidas = [];
        foreach ($producto->gruposModificadores as $grupo) {
            foreach ($grupo->modificadores as $mod) {
                $permitidas[$mod->id] = [$mod, $grupo];
            }
        }

        // Selección: id => cantidad
        $seleccion = collect($item['modificadores'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->countBy();

        foreach ($seleccion->keys() as $id) {
            if (!isset($permitidas[$id])) {
                throw new InvalidArgumentException("Una de las opciones no aplica para {$producto->nombre}.");
            }
            if (!$permitidas[$id][0]->disponibleHoy()) {
                throw new InvalidArgumentException("{$permitidas[$id][0]->nombre} no está disponible hoy.");
            }
        }

        // Reglas por grupo: mínimo, máximo, incluidos y adicionales
        $lineas      = [];
        $adicionales = 0;

        foreach ($producto->gruposModificadores as $grupo) {
            $pivot    = $grupo->pivot;
            $elegidos = $seleccion->filter(fn ($cant, $id) => $permitidas[$id][1]->id === $grupo->id);
            $total    = (int) $elegidos->sum();

            if ($total < (int) $pivot->minimo) {
                throw new InvalidArgumentException(
                    "{$producto->nombre}: elige al menos {$pivot->minimo} en {$grupo->nombre}."
                );
            }

            if ($pivot->maximo !== null && $total > (int) $pivot->maximo) {
                throw new InvalidArgumentException(
                    "{$producto->nombre}: máximo {$pivot->maximo} en {$grupo->nombre}."
                );
            }

            $incluidosRestantes = (int) $pivot->incluidos;
            $precioGrupo        = (int) ($pivot->precio_extra ?? $grupo->precio_extra);

            foreach ($elegidos as $id => $cantidad) {
                $mod       = $permitidas[$id][0];
                $precioMod = $mod->precio_extra ?? $precioGrupo;

                $gratis              = min($cantidad, $incluidosRestantes);
                $incluidosRestantes -= $gratis;
                $cobrados            = $cantidad - $gratis;

                if ($gratis > 0) {
                    $lineas[] = $this->linea($mod, $grupo, $gratis, 0);
                }

                if ($cobrados > 0) {
                    $lineas[]     = $this->linea($mod, $grupo, $cobrados, $precioMod);
                    $adicionales += $cobrados * $precioMod;
                }
            }
        }

        $cantidad = max(1, (int) ($item['cantidad'] ?? 1));
        $unitario = $precioBase + $adicionales;

        return [
            'producto'           => $producto,
            'variante'           => $variante,
            'cantidad'           => $cantidad,
            'precio_base'        => $precioBase,
            'precio_adicionales' => $adicionales,
            'precio_unitario'    => $unitario,
            'subtotal'           => $unitario * $cantidad,
            'modificadores'      => $lineas,
            'notas'              => isset($item['notas']) ? trim((string) $item['notas']) ?: null : null,
        ];
    }

    private function linea($mod, $grupo, int $cantidad, int $precio): array
    {
        return [
            'modificador_id'  => $mod->id,
            'grupo_id'        => $grupo->id,
            'nombre_grupo'    => $grupo->nombre,
            'nombre'          => $mod->nombre,
            'cantidad'        => $cantidad,
            'precio_unitario' => $precio,
        ];
    }
}