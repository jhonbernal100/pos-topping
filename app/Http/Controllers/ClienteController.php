<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', compact('clientes'));
    }

    public function crear()
    {
        return view('clientes.crear');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:191',
        ]);

        $cliente = Cliente::create([
            'tenant_id'        => session('tenant_id'),
            'nombre'           => $request->nombre,
            'tipo_documento'   => $request->tipo_documento ?? 'CC',
            'numero_documento' => $request->numero_documento,
            'telefono'         => $request->telefono,
            'email'            => $request->email,
            'direccion'        => $request->direccion,
            'ciudad'           => $request->ciudad,
            'activo'           => true,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente creado correctamente',
            'cliente' => $cliente,
        ]);
    }

    public function editar(Cliente $cliente)
    {
        return view('clientes.editar', compact('cliente'));
    }

    public function actualizar(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre' => 'required|string|max:191',
        ]);

        $cliente->update([
            'nombre'           => $request->nombre,
            'tipo_documento'   => $request->tipo_documento ?? 'CC',
            'numero_documento' => $request->numero_documento,
            'telefono'         => $request->telefono,
            'email'            => $request->email,
            'direccion'        => $request->direccion,
            'ciudad'           => $request->ciudad,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente actualizado correctamente',
        ]);
    }
}