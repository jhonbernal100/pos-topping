<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::where('tenant_id', session('tenant_id'))
            ->where('rol', '!=', 'superadmin')
            ->orderBy('name')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function crear()
    {
        return view('usuarios.crear');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'rol'   => 'required|in:dueno,auxiliar',
        ]);

        $password = Str::random(10);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($password),
            'tenant_id' => session('tenant_id'),
            'rol'       => $request->rol,
            'activo'    => true,
        ]);

        Mail::raw(
            "Hola {$request->name},\n\n" .
            "El administrador de " . auth()->user()->tenant->nombre . " te ha creado una cuenta en POS Topping.\n\n" .
            "Accede en: https://pos-topping.avanzas.digital/login\n" .
            "Usuario: {$request->email}\n" .
            "Contrasena temporal: {$password}\n\n" .
            "Te recomendamos cambiar tu contrasena al ingresar.\n\n" .
            "Avanzas Digital - Tu exito es nuestro objetivo",
            function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Acceso a POS Topping - ' . auth()->user()->tenant->nombre);
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
        return view('usuarios.editar', compact('usuario'));
    }

    public function actualizar(Request $request, User $usuario)
    {
        $this->verificarAcceso($usuario);

        $request->validate([
            'name'  => 'required|string|max:191',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'rol'   => 'required|in:dueno,auxiliar',
        ]);

        $usuario->update([
            'name'  => $request->name,
            'email' => $request->email,
            'rol'   => $request->rol,
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

    // Verifica que el usuario pertenece al tenant actual
    private function verificarAcceso(User $usuario)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        if ((int)$usuario->tenant_id !== (int)$tenantId) {
            abort(403, 'No autorizado');
        }
    }
}