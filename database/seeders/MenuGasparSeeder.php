<?php

namespace Database\Seeders;

use App\Models\CategoriaMenu;
use App\Models\GrupoModificador;
use App\Models\Modificador;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class MenuGasparSeeder extends Seeder
{
    private const FIN_DE_SEMANA = [6, 7]; // sábado y domingo

    private int $tenantId;
    private array $g = [];

    public function run(): void
    {
        $tenant = Tenant::where('nit', '529934889-8')->firstOrFail();
        $this->tenantId = $tenant->id;

        // ---------------------------------------------------------------
        // Categorías
        // ---------------------------------------------------------------
        $cat = [];
        foreach (['Merengones', 'Especiales', 'Postres', 'Helados', 'Bebidas'] as $i => $nombre) {
            $cat[$nombre] = CategoriaMenu::updateOrCreate(
                ['tenant_id' => $this->tenantId, 'nombre' => $nombre],
                ['orden' => $i + 1, 'activo' => true]
            );
        }

        // ---------------------------------------------------------------
        // Grupos de modificadores
        // ---------------------------------------------------------------
        $this->g['sabor'] = $this->grupo('Sabor de helado', 0, 1, [
            'Vainilla francesa', 'Yogurt',
        ]);

        $this->g['frutas'] = $this->grupo('Frutas', 2500, 2, [
            'Fresa', 'Mango', 'Banano en salsa de la casa', 'Papayuela', 'Maracuyá',
            'Guanábana', 'Durazno en almíbar', 'Coco rayado',
            ['Sandía', self::FIN_DE_SEMANA],
        ]);

        $this->g['toppings'] = $this->grupo('Toppings', 2500, 3, [
            'Chips de merengón', 'Chips de chocolate', 'Trozos de brownie', 'Gelatina',
            'Galleta Oreo', 'Mini Chocorramo', 'Mini churros', 'M&M', 'Gomas de animales',
            'Mini masmelos', 'Barquillos', 'Quipitos', 'Gusanos ácidos', 'Miga de red velvet',
            'Maní confitado', 'Nanos gomas', 'Granola', 'Galleta de cono triturada',
            'Cereal recubierto de chocolate', 'Fresa Bom Bom Bum',
            'Perlas explosivas de blueberry', 'Perlas explosivas de manzana verde',
            'Perlas explosivas de arándanos',
        ]);

        $this->g['salsas'] = $this->grupo('Salsas', 0, 4, [
            'Mora (de la casa)', 'Fresa (de la casa)', 'Arequipe', "Chocolate Hershey's",
        ]);

        $this->g['helado_extra'] = $this->grupo('Adición de helado', 3000, 5, [
            'Porción extra de helado',
        ]);

        $this->g['sabor_smoothie'] = $this->grupo('Sabor de smoothie', 0, 6, [
            'Frutos rojos', 'Frutos amarillos',
        ]);

        // Configuración reutilizable: [incluidos, minimo, maximo]
        $sabor   = ['sabor'        => [1, 1, 1]];
        $salsa1  = ['salsas'       => [1, 0, 1]];
        $extras  = [
            'frutas'       => [0, 0, null],
            'toppings'     => [0, 0, null],
            'helado_extra' => [0, 0, 3],
        ];

        // ---------------------------------------------------------------
        // Merengones
        // ---------------------------------------------------------------
        $p = $this->producto($cat['Merengones'], 1, 'Merengón Grande', 21500,
            'Helado, 4 frutas, 3 toppings, 1 barquillo y 1 salsa.');
        $this->grupos($p, $sabor + ['frutas' => [4, 0, null], 'toppings' => [3, 0, null]] + $salsa1 + ['helado_extra' => [0, 0, 3]]);

        $p = $this->producto($cat['Merengones'], 2, 'Merengón Mediano', 18000,
            'Helado, 3 frutas, 2 toppings, 1 barquillo y 1 salsa.');
        $this->grupos($p, $sabor + ['frutas' => [3, 0, null], 'toppings' => [2, 0, null]] + $salsa1 + ['helado_extra' => [0, 0, 3]]);

        $p = $this->producto($cat['Merengones'], 3, 'Baby Merengón', 11000,
            'Helado, 2 frutas, 1 topping y 1 salsa.');
        $this->grupos($p, $sabor + ['frutas' => [2, 0, null], 'toppings' => [1, 0, null]] + $salsa1 + ['helado_extra' => [0, 0, 3]]);

        // ---------------------------------------------------------------
        // Especiales
        // ---------------------------------------------------------------
        $p = $this->producto($cat['Especiales'], 1, 'Fresas con Chocolate', 18900,
            '250 g de fresas bañadas en chocolate con 2 capas de helado.');
        $this->grupos($p, $sabor + $extras);

        $p = $this->producto($cat['Especiales'], 2, 'Nébula', 17900,
            'Churros crocantes con chocolate, grageas de fresa, chocolatina rosa, algodón de azúcar, 1 salsa y helado.',
            ['domicilio' => false]);
        $this->grupos($p, $sabor + $salsa1 + $extras);

        $p = $this->producto($cat['Especiales'], 3, 'Roll Cake', 14500,
            'Bizcocho relleno de durazno con frosting de queso crema, helado y trozos de fruta.',
            ['dias' => self::FIN_DE_SEMANA]);
        $this->grupos($p, $sabor + $extras);

        $p = $this->producto($cat['Especiales'], 4, 'Red Velvet', 16900,
            '3 capas de helado, 3 niveles de red velvet, fresas, arándanos y 1 salsa.');
        $this->grupos($p, $sabor + $salsa1 + $extras);

        $p = $this->producto($cat['Especiales'], 5, 'Gaspar Greek', 14900,
            '210 g de yogurt griego artesanal, 2 frutas, 1 topping y 1 salsa.');
        $this->grupos($p, ['frutas' => [2, 0, null], 'toppings' => [1, 0, null]] + $salsa1);

        $p = $this->producto($cat['Especiales'], 6, 'Conochurro', 12900,
            'Churro crocante con helado y 1 salsa.',
            ['dias' => self::FIN_DE_SEMANA]);
        $this->grupos($p, $sabor + $salsa1 + $extras);

        $p = $this->producto($cat['Especiales'], 7, 'Mexicanísimo', 10500,
            'Base de mango, helado, tajín y salsa chamoy de la casa.');
        $this->grupos($p, $sabor + $extras);

        // ---------------------------------------------------------------
        // Postres
        // ---------------------------------------------------------------
        $p = $this->producto($cat['Postres'], 1, 'Torta de Chocolate', 14500,
            'Porción personal con helado o torta completa sin helado.');
        $this->variantes($p, [['Porción personal con helado', 14500], ['Torta completa sin helado', 100000]]);
        $this->grupos($p, ['sabor' => [1, 0, 1]]); // opcional: solo aplica a la porción

        $p = $this->producto($cat['Postres'], 2, 'Taiyaki', 13900,
            'Taiyaki con helado y salsa. Fantasy incluye crema de Nutella.');
        $this->variantes($p, [['Sencillo', 13900], ['Fantasy', 18900]]);
        $this->grupos($p, $sabor + $salsa1 + $extras);

        // ---------------------------------------------------------------
        // Helados
        // ---------------------------------------------------------------
        $p = $this->producto($cat['Helados'], 1, 'Helado con 2 toppings', 9900,
            'Helado con 2 toppings y 1 salsa.');
        $this->variantes($p, [['Vainilla francesa', 9900], ['Yogurt', 10900]]);
        $this->grupos($p, ['toppings' => [2, 0, null], 'frutas' => [0, 0, null]] + $salsa1 + ['helado_extra' => [0, 0, 3]]);

        $p = $this->producto($cat['Helados'], 2, 'Parfait', 14900,
            '3 capas de helado, 3 toppings, 1 salsa y 1 barquillo.');
        $this->variantes($p, [['Vainilla francesa', 14900], ['Yogurt', 15900]]);
        $this->grupos($p, ['toppings' => [3, 0, null], 'frutas' => [0, 0, null]] + $salsa1 + ['helado_extra' => [0, 0, 3]]);

        $p = $this->producto($cat['Helados'], 3, 'Helado en Cono', 4500,
            'Helado en cono con 1 salsa.');
        $this->variantes($p, [['Vainilla francesa', 4500], ['Yogurt', 5000]]);
        $this->grupos($p, $salsa1 + $extras);

        $p = $this->producto($cat['Helados'], 4, 'Oblea Gaspar', 13000,
            'Helado, queso, 2 frutas y 2 salsas.');
        $this->grupos($p, $sabor + ['frutas' => [2, 0, null], 'salsas' => [2, 0, 2], 'toppings' => [0, 0, null]]);

        $p = $this->producto($cat['Helados'], 5, 'Oblea Sencilla', 6500,
            'Oblea con queso y salsas.');
        $this->grupos($p, ['salsas' => [2, 0, 2]]);

        // ---------------------------------------------------------------
        // Bebidas
        // ---------------------------------------------------------------
        $p = $this->producto($cat['Bebidas'], 1, 'Smoothie', 14900,
            'Smoothie de frutos rojos o frutos amarillos con helado.');
        $this->variantes($p, [['Vainilla francesa', 14900], ['Yogurt', 15900]]);
        $this->grupos($p, ['sabor_smoothie' => [1, 1, 1]]);

        $p = $this->producto($cat['Bebidas'], 2, 'Agua Manantial 500 ml', 5000,
            'Agua de manantial en botella de vidrio de 500 ml.',
            ['tipo' => 'reventa', 'stock_minimo' => 12]);
        $this->grupos($p, []);

        $this->resumen();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function grupo(string $nombre, int $precio, int $orden, array $opciones): GrupoModificador
    {
        $grupo = GrupoModificador::updateOrCreate(
            ['tenant_id' => $this->tenantId, 'nombre' => $nombre],
            ['precio_extra' => $precio, 'orden' => $orden, 'activo' => true]
        );

        foreach ($opciones as $i => $op) {
            [$nombreOp, $dias] = is_array($op) ? $op : [$op, null];

            Modificador::updateOrCreate(
                ['grupo_id' => $grupo->id, 'nombre' => $nombreOp],
                ['dias_disponibles' => $dias, 'orden' => $i + 1, 'activo' => true]
            );
        }

        return $grupo;
    }

    private function producto(CategoriaMenu $cat, int $orden, string $nombre, int $precio, string $descripcion, array $op = []): Producto
    {
        $producto = Producto::withTrashed()->updateOrCreate(
            ['tenant_id' => $this->tenantId, 'nombre' => $nombre],
            [
                'categoria_menu_id'    => $cat->id,
                'categoria'            => $cat->nombre,
                'tipo_menu'            => $op['tipo'] ?? 'preparado',
                'unidad'               => 'unidad',
                'precio_compra'        => 0,
                'precio_venta'         => $precio,
                'stock'                => $op['stock'] ?? 0,
                'stock_minimo'         => $op['stock_minimo'] ?? 0,
                'descripcion'          => $descripcion,
                'disponible_local'     => true,
                'disponible_domicilio' => $op['domicilio'] ?? true,
                'dias_disponibles'     => $op['dias'] ?? null,
                'orden_menu'           => $orden,
                'activo'               => true,
            ]
        );

        if ($producto->trashed()) {
            $producto->restore();
        }

        return $producto;
    }

    private function variantes(Producto $producto, array $variantes): void
    {
        foreach ($variantes as $i => [$nombre, $precio]) {
            ProductoVariante::updateOrCreate(
                ['producto_id' => $producto->id, 'nombre' => $nombre],
                ['precio' => $precio, 'orden' => $i + 1, 'activo' => true]
            );
        }

        // El precio base del producto es el de su variante más económica
        $producto->update(['precio_venta' => min(array_column($variantes, 1))]);
    }

    // $config: ['clave_grupo' => [incluidos, minimo, maximo]]
    private function grupos(Producto $producto, array $config): void
    {
        $sync  = [];
        $orden = 1;

        foreach ($config as $clave => [$incluidos, $minimo, $maximo]) {
            $sync[$this->g[$clave]->id] = [
                'incluidos'    => $incluidos,
                'minimo'       => $minimo,
                'maximo'       => $maximo,
                'precio_extra' => null,
                'orden'        => $orden++,
            ];
        }

        $producto->gruposModificadores()->sync($sync);
    }

    private function resumen(): void
    {
        $filas = Producto::with(['categoriaMenu', 'variantes', 'gruposModificadores'])
            ->where('tenant_id', $this->tenantId)
            ->whereNotNull('categoria_menu_id')
            ->get()
            ->sortBy(fn ($p) => sprintf('%03d-%03d', $p->categoriaMenu->orden, $p->orden_menu))
            ->map(fn ($p) => [
                $p->categoriaMenu->nombre,
                $p->nombre,
                '$' . number_format($p->precio_venta, 0, ',', '.'),
                $p->variantes->count(),
                $p->gruposModificadores->pluck('nombre')->join(', '),
                $p->dias_disponibles ? 'Sab-Dom' : 'Todos',
                $p->disponible_domicilio ? 'Si' : 'No',
            ])
            ->values()
            ->all();

        $this->command->table(
            ['Categoria', 'Producto', 'Desde', 'Var.', 'Grupos', 'Dias', 'Domicilio'],
            $filas
        );
    }
}