<?php

namespace App\Http\Controllers;

use App\Models\CategoriaMenu;
use App\Models\GrupoModificador;
use App\Models\Modificador;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Services\ImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

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

    // ---------------------------------------------------------------
    // Crear / editar producto
    // ---------------------------------------------------------------

    public function crear()
    {
        $this->soloPropietario();

        $producto = new Producto([
            'tipo_menu'            => 'preparado',
            'disponible_local'     => true,
            'disponible_domicilio' => true,
            'activo'               => true,
            'precio_venta'         => 0,
        ]);

        return $this->formulario($producto);
    }

    public function editar(Producto $producto)
    {
        $this->soloPropietario();
        $producto->load(['variantes', 'gruposModificadores']);

        return $this->formulario($producto);
    }

    public function store(Request $request)
    {
        $this->soloPropietario();

        $tenant = auth()->user()->tenant;
        $limite = $tenant->limiteProductos();

        if ($limite !== null && Producto::count() >= $limite) {
            return response()->json([
                'success' => false,
                'mensaje' => "La demo permite hasta {$limite} productos.",
            ], 422);
        }

        return $this->guardar($request, new Producto());
    }

    public function actualizar(Request $request, Producto $producto)
    {
        $this->soloPropietario();

        return $this->guardar($request, $producto);
    }

    // ---------------------------------------------------------------
    // Disponibilidad rápida
    // ---------------------------------------------------------------

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

    // ---------------------------------------------------------------
    // Toppings y grupos de opciones
    // ---------------------------------------------------------------

    public function guardarModificador(Request $request, GrupoModificador $grupo)
    {
        $this->soloPropietario();
        $data = $this->validarModificador($request);

        if ($grupo->modificadores()->whereRaw('LOWER(nombre) = ?', [mb_strtolower($data['nombre'])])->exists()) {
            return $this->errorMenu("{$data['nombre']} ya existe en {$grupo->nombre}.");
        }

        $mod = $grupo->modificadores()->create($data + [
            'orden'  => (int) $grupo->modificadores()->max('orden') + 1,
            'activo' => true,
        ]);

        return response()->json(['success' => true, 'mensaje' => "{$mod->nombre} agregado a {$grupo->nombre}", 'id' => $mod->id]);
    }

    public function actualizarModificador(Request $request, Modificador $modificador)
    {
        $this->soloPropietario();
        $grupo = $this->grupoDe($modificador);
        $data  = $this->validarModificador($request);

        $repetido = $grupo->modificadores()->whereKeyNot($modificador->id)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($data['nombre'])])->exists();
        if ($repetido) {
            return $this->errorMenu("{$data['nombre']} ya existe en {$grupo->nombre}.");
        }

        $modificador->update($data);

        return response()->json(['success' => true, 'mensaje' => "{$modificador->nombre} actualizado"]);
    }

    // Las ventas guardan el nombre de la opción, así que el histórico no se pierde
    public function eliminarModificador(Modificador $modificador)
    {
        $this->soloPropietario();
        $this->grupoDe($modificador);
        $nombre = $modificador->nombre;
        $modificador->delete();

        return response()->json(['success' => true, 'mensaje' => "{$nombre} eliminado"]);
    }

    public function guardarGrupo(Request $request)
    {
        $this->soloPropietario();
        $data = $this->validarGrupo($request);

        if (GrupoModificador::whereRaw('LOWER(nombre) = ?', [mb_strtolower($data['nombre'])])->exists()) {
            return $this->errorMenu("Ya existe un grupo llamado {$data['nombre']}.");
        }

        $grupo = GrupoModificador::create($data + [
            'tenant_id' => (int) session('tenant_id'),
            'orden'     => (int) GrupoModificador::max('orden') + 1,
            'activo'    => true,
        ]);

        return response()->json(['success' => true, 'mensaje' => "Grupo {$grupo->nombre} creado", 'id' => $grupo->id]);
    }

    public function actualizarGrupo(Request $request, GrupoModificador $grupo)
    {
        $this->soloPropietario();
        $data = $this->validarGrupo($request);

        if (GrupoModificador::whereKeyNot($grupo->id)->whereRaw('LOWER(nombre) = ?', [mb_strtolower($data['nombre'])])->exists()) {
            return $this->errorMenu("Ya existe un grupo llamado {$data['nombre']}.");
        }

        $grupo->update($data);

        return response()->json(['success' => true, 'mensaje' => "Grupo {$grupo->nombre} actualizado"]);
    }

    private function validarModificador(Request $request): array
    {
        $data = $request->validate([
            'nombre'             => 'required|string|max:100',
            'precio_extra'       => 'nullable|integer|min:0|max:1000000',
            'dias_disponibles'   => 'nullable|array',
            'dias_disponibles.*' => 'integer|between:1,7',
        ], ['nombre.required' => 'Escribe el nombre del topping.']);

        $dias = collect($data['dias_disponibles'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

        return [
            'nombre'           => trim($data['nombre']),
            'precio_extra'     => isset($data['precio_extra']) ? (int) $data['precio_extra'] : null,   // null = precio del grupo
            'dias_disponibles' => count($dias) === 0 || count($dias) === 7 ? null : $dias,
        ];
    }

    private function validarGrupo(Request $request): array
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:100',
            'precio_extra' => 'nullable|integer|min:0|max:1000000',
        ], ['nombre.required' => 'Escribe el nombre del grupo.']);

        return ['nombre' => trim($data['nombre']), 'precio_extra' => (int) ($data['precio_extra'] ?? 0)];
    }

    // El grupo se consulta con TenantScope: si la opción es de otro negocio, no se encuentra
    private function grupoDe(Modificador $modificador): GrupoModificador
    {
        $grupo = GrupoModificador::find($modificador->grupo_id);
        if (!$grupo) {
            abort(403, 'No autorizado');
        }
        return $grupo;
    }

    private function errorMenu(string $mensaje)
    {
        return response()->json(['success' => false, 'mensaje' => $mensaje], 422);
    }

    // ---------------------------------------------------------------
    // Fotos de productos
    // ---------------------------------------------------------------

    public function subirFoto(Request $request, Producto $producto, ImagenService $imagenes)
    {
        $this->soloPropietario();

        $request->validate(
            ['foto' => 'required|image|max:10240'],
            ['foto.max' => 'La foto no puede pesar más de 10 MB.', 'foto.image' => 'El archivo debe ser una imagen.']
        );

        try {
            $ruta = $imagenes->guardar($request->file('foto'), 'productos/' . session('tenant_id'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }

        $imagenes->borrar($producto->foto);
        $producto->update(['foto' => $ruta]);

        return response()->json([
            'success' => true,
            'url'     => asset('storage/' . $ruta),
            'mensaje' => 'Foto actualizada',
        ]);
    }

    public function quitarFoto(Producto $producto, ImagenService $imagenes)
    {
        $this->soloPropietario();

        $imagenes->borrar($producto->foto);
        $producto->update(['foto' => null]);

        return response()->json(['success' => true, 'mensaje' => 'Foto eliminada']);
    }

    // ---------------------------------------------------------------
    // Apariencia de la caja (logo y colores)
    // ---------------------------------------------------------------

    public function apariencia()
    {
        $this->soloPropietario();

        return view('menu.apariencia', ['tenant' => auth()->user()->tenant]);
    }

    public function guardarApariencia(Request $request, ImagenService $imagenes)
    {
        $this->soloPropietario();

        $color = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        $data = $request->validate([
            'color_primario'   => $color,
            'color_secundario' => $color,
            'color_fondo'      => $color,
            'logo'             => 'nullable|image|max:10240',
            'quitar_logo'      => 'nullable|boolean',
        ]);

        $tenant = auth()->user()->tenant;

        $cambios = [
            'color_primario'   => strtoupper($data['color_primario']),
            'color_secundario' => strtoupper($data['color_secundario']),
            'color_fondo'      => strtoupper($data['color_fondo']),
        ];

        if ($request->boolean('quitar_logo')) {
            $imagenes->borrar($tenant->logo);
            $cambios['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            try {
                $ruta = $imagenes->guardar($request->file('logo'), 'logos/' . $tenant->id, 600);
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['logo' => $e->getMessage()]);
            }
            $imagenes->borrar($tenant->logo);
            $cambios['logo'] = $ruta;
        }

        $tenant->update($cambios);

        return redirect('/menu/apariencia')->with('ok', 'Apariencia guardada. Abre Nueva venta para verla.');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function formulario(Producto $producto)
    {
        $categorias = CategoriaMenu::orderBy('orden')->get();
        $grupos     = GrupoModificador::orderBy('orden')->withCount('modificadores')->get();

        return view('menu.producto', compact('producto', 'categorias', 'grupos'));
    }

    private function guardar(Request $request, Producto $producto)
    {
        $tenantId = (int) session('tenant_id');

        $data = $request->validate([
            'nombre'               => 'required|string|max:191',
            'descripcion'          => 'nullable|string|max:1000',
            'categoria_menu_id'    => ['required', 'integer', Rule::exists('categorias_menu', 'id')->where('tenant_id', $tenantId)],
            'tipo_menu'            => 'required|in:preparado,reventa',
            'precio_venta'         => 'required|integer|min:0',
            'disponible_local'     => 'boolean',
            'disponible_domicilio' => 'boolean',
            'dias_disponibles'     => 'nullable|array',
            'dias_disponibles.*'   => 'integer|between:1,7',
            'variantes'            => 'nullable|array',
            'variantes.*.id'       => 'nullable|integer',
            'variantes.*.nombre'   => 'required|string|max:100',
            'variantes.*.precio'   => 'required|integer|min:0',
            'variantes.*.activo'   => 'boolean',
            'grupos'               => 'nullable|array',
            'grupos.*.grupo_id'    => ['required', 'integer', Rule::exists('grupos_modificadores', 'id')->where('tenant_id', $tenantId)],
            'grupos.*.incluidos'   => 'required|integer|min:0|max:20',
            'grupos.*.minimo'      => 'required|integer|min:0|max:20',
            'grupos.*.maximo'      => 'nullable|integer|min:0|max:20',
        ]);

        foreach ($data['grupos'] ?? [] as $g) {
            $max = $g['maximo'] ?? null;
            if ($max !== null && ($g['minimo'] > $max || $g['incluidos'] > $max)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'En cada grupo, incluidos y mínimo no pueden superar el máximo.',
                ], 422);
            }
        }

        // Días: vacío o los 7 = disponible todos los días
        $dias = collect($data['dias_disponibles'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();
        if (count($dias) === 0 || count($dias) === 7) {
            $dias = null;
        }

        $variantes = collect($data['variantes'] ?? [])->values();
        $activas   = $variantes->filter(fn ($v) => $v['activo'] ?? true);
        $precio    = $activas->isNotEmpty() ? (int) $activas->min('precio') : (int) $data['precio_venta'];
        $categoria = CategoriaMenu::find($data['categoria_menu_id']);

        DB::transaction(function () use ($producto, $data, $dias, $variantes, $precio, $categoria, $tenantId) {
            $producto->fill([
                'nombre'               => $data['nombre'],
                'descripcion'          => $data['descripcion'] ?? null,
                'categoria_menu_id'    => $categoria->id,
                'categoria'            => $categoria->nombre,
                'tipo_menu'            => $data['tipo_menu'],
                'precio_venta'         => $precio,
                'disponible_local'     => $data['disponible_local'] ?? true,
                'disponible_domicilio' => $data['disponible_domicilio'] ?? true,
                'dias_disponibles'     => $dias,
            ]);

            if (!$producto->exists) {
                $producto->tenant_id     = $tenantId;
                $producto->unidad        = 'unidad';
                $producto->precio_compra = 0;
                $producto->stock         = 0;
                $producto->stock_minimo  = 0;
                $producto->activo        = true;
                $producto->orden_menu    = (int) Producto::where('categoria_menu_id', $categoria->id)->max('orden_menu') + 1;
            }

            $producto->save();

            // Variantes: actualizar las existentes, crear nuevas y borrar las que se quitaron
            $conservar = [];
            foreach ($variantes as $i => $v) {
                $variante = !empty($v['id'])
                    ? ProductoVariante::where('producto_id', $producto->id)->whereKey($v['id'])->first()
                    : null;

                $variante ??= new ProductoVariante(['producto_id' => $producto->id]);
                $variante->fill([
                    'nombre' => $v['nombre'],
                    'precio' => (int) $v['precio'],
                    'activo' => $v['activo'] ?? true,
                    'orden'  => $i + 1,
                ])->save();

                $conservar[] = $variante->id;
            }

            ProductoVariante::where('producto_id', $producto->id)
                ->whereNotIn('id', $conservar ?: [0])
                ->delete();

            // Grupos de opciones
            $sync = [];
            foreach (array_values($data['grupos'] ?? []) as $i => $g) {
                $sync[$g['grupo_id']] = [
                    'incluidos'    => $g['incluidos'],
                    'minimo'       => $g['minimo'],
                    'maximo'       => $g['maximo'] ?? null,
                    'precio_extra' => null,
                    'orden'        => $i + 1,
                ];
            }
            $producto->gruposModificadores()->sync($sync);
        });

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto guardado correctamente',
            'id'      => $producto->id,
        ]);
    }

    private function soloPropietario(): void
    {
        if (!auth()->user()?->esPropietario()) {
            abort(403, 'Solo el propietario puede administrar el menú');
        }
    }
}
