<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class UsuarioController extends Controller
{
    public function index()
    {
        $actual = auth()->user();

        $usuarios = User::with('sede')
            ->where('tenant_id', $this->tenantId())
            ->where('rol', '!=', 'superadmin')
            ->when($actual->esGerenteSede(), fn ($q) => $q->where('sede_id', $actual->sede_id))
            ->orderBy('name')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function crear()
    {
        $sedes = $this->sedesDisponibles();
        return view('usuarios.crear', compact('sedes'));
    }

    public function store(Request $request)
    {
        $actual = auth()->user();
        $tenant = $actual->tenant;

        $request->validate([
            'name'    => 'required|string|max:191',
            'email'   => 'required|email|unique:users,email',
            'rol'     => 'required|in:dueno,auxiliar',
            'sede_id' => 'nullable|integer',
        ]);

        [$rol, $sedeId] = $this->resolverRolYSede($request, $actual);

        if ($error = $this->validarLimites($tenant, $sedeId)) {
            return response()->json(['success' => false, 'mensaje' => $error], 422);
        }

        $password = Str::random(10);

        $usuario = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($password),
            'tenant_id' => $tenant->id,
            'sede_id'   => $sedeId,
            'rol'       => $rol,
            'activo'    => true,
        ]);

        $lugar = $usuario->sede
            ? $tenant->nombre . ' - Sede ' . $usuario->sede->nombre
            : $tenant->nombre;

        Mail::raw(
            "Hola {$request->name},\n\n" .
            "El administrador de {$lugar} te ha creado una cuenta en POS Topping.\n\n" .
            "Accede en: https://pos-topping.avanzas.digital/login\n" .
            "Usuario: {$request->email}\n" .
            "Contrasena temporal: {$password}\n\n" .
            "Te recomendamos cambiar tu contrasena al ingresar.\n\n" .
            "Avanzas Digital - Tu exito es nuestro objetivo",
            function ($message) use ($request, $lugar) {
                $message->to($request->email)
                        ->subject('Acceso a POS Topping - ' . $lugar);
            }
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario creado y credenciales enviadas a ' . $request->email,
        ]);
    }

    public function editar(User $usuario)
    {
        $this->verificarAcceso($usuario);
        $sedes = $this->sedesDisponibles();
        return view('usuarios.editar', compact('usuario', 'sedes'));
    }

    public function actualizar(Request $request, User $usuario)
    {
        $this->verificarAcceso($usuario);
        $actual = auth()->user();

        $request->validate([
            'name'    => 'required|string|max:191',
            'email'   => 'required|email|unique:users,email,' . $usuario->id,
            'rol'     => 'required|in:dueno,auxiliar',
            'sede_id' => 'nullable|integer',
        ]);

        [$rol, $sedeId] = $this->resolverRolYSede($request, $actual);

        // Si cambia de sede, validar cupo en la sede destino
        if ((int) $sedeId !== (int) $usuario->sede_id || $rol !== $usuario->rol) {
            if ($error = $this->validarLimites($actual->tenant, $sedeId, $usuario->id)) {
                return response()->json(['success' => false, 'mensaje' => $error], 422);
            }
        }

        $usuario->update([
            'name'    => $request->name,
            'email'   => $request->email,
            'rol'     => $rol,
            'sede_id' => $sedeId,
        ]);

        if ($request->password) {
            $usuario->update(['password' => bcrypt($request->password)]);
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario actualizado correctamente',
        ]);
    }

    public function toggleActivo(User $usuario)
    {
        $this->verificarAcceso($usuario);

        if ($usuario->id === auth()->id()) {
            return response()->json(['success' => false, 'mensaje' => 'No puedes desactivarte a ti mismo'], 422);
        }

        $usuario->update(['activo' => !$usuario->activo]);

        return response()->json([
            'success' => true,
            'activo'  => $usuario->activo,
            'mensaje' => $usuario->activo ? 'Usuario activado' : 'Usuario desactivado',
        ]);
    }

    public function eliminar(User $usuario)
    {
        $this->verificarAcceso($usuario);

        if ($usuario->id === auth()->id()) {
            return response()->json(['success' => false, 'mensaje' => 'No puedes eliminarte a ti mismo'], 422);
        }

        $usuario->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario eliminado correctamente',
        ]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function tenantId(): int
    {
        return (int) (session('tenant_id') ?? auth()->user()->tenant_id);
    }

    // Sedes que el usuario actual puede asignar
    private function sedesDisponibles()
    {
        $actual = auth()->user();

        return Sede::where('tenant_id', $this->tenantId())
            ->where('activo', true)
            ->when($actual->esGerenteSede(), fn ($q) => $q->where('id', $actual->sede_id))
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Reglas:
     * - Gerente de sede: solo crea auxiliares de su propia sede.
     * - Propietario: puede crear propietarios (sin sede), gerentes (dueno + sede) o auxiliares (con sede).
     * - Todo auxiliar debe tener sede.
     */
    private function resolverRolYSede(Request $request, User $actual): array
    {
        if ($actual->esGerenteSede()) {
            return ['auxiliar', $actual->sede_id];
        }

        $rol    = $request->rol;
        $sedeId = $request->sede_id ?: null;

        if ($sedeId) {
            $valida = Sede::where('id', $sedeId)
                ->where('tenant_id', $this->tenantId())
                ->exists();

            if (!$valida) {
                abort(response()->json(['success' => false, 'mensaje' => 'Sede no valida'], 422));
            }
        }

        if ($rol === 'auxiliar' && !$sedeId) {
            abort(response()->json(['success' => false, 'mensaje' => 'Selecciona la sede del auxiliar'], 422));
        }

        return [$rol, $sedeId];
    }

    // Devuelve un mensaje de error si se supera el límite de la demo, o null si está permitido
    private function validarLimites($tenant, ?int $sedeId, ?int $excluirUsuarioId = null): ?string
    {
        if (!$tenant->esTrial()) {
            return null;
        }

        $query = User::where('tenant_id', $tenant->id)
            ->where('rol', '!=', 'superadmin')
            ->when($excluirUsuarioId, fn ($q) => $q->where('id', '!=', $excluirUsuarioId));

        // Propietarios (sin sede): máximo 1 en la demo
        if (!$sedeId) {
            $propietarios = (clone $query)->whereNull('sede_id')->count();
            return $propietarios >= 1
                ? 'En la demo solo se permite un propietario. Asigna este usuario a una sede.'
                : null;
        }

        $limite    = $tenant->limiteUsuariosPorSede();
        $enLaSede  = (clone $query)->where('sede_id', $sedeId)->count();

        return $enLaSede >= $limite
            ? "La demo permite hasta {$limite} usuarios por sede. Esta sede ya alcanzo el limite."
            : null;
    }

    // Verifica que el usuario pertenece al tenant (y a la sede, si es gerente de sede)
    private function verificarAcceso(User $usuario): void
    {
        $actual = auth()->user();

        if ((int) $usuario->tenant_id !== $this->tenantId()) {
            abort(403, 'No autorizado');
        }

        if ($actual->esGerenteSede() && (int) $usuario->sede_id !== (int) $actual->sede_id) {
            abort(403, 'No autorizado');
        }
    }
}