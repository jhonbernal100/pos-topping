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
        $request->validate($this->reglas(), $this->mensajes());

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
        ] + $this->convenio($request));

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
        $request->validate($this->reglas(), $this->mensajes());

        $cliente->update([
            'nombre'           => $request->nombre,
            'tipo_documento'   => $request->tipo_documento ?? 'CC',
            'numero_documento' => $request->numero_documento,
            'telefono'         => $request->telefono,
            'email'            => $request->email,
            'direccion'        => $request->direccion,
            'ciudad'           => $request->ciudad,
        ] + $this->convenio($request));

        return response()->json([
            'success' => true,
            'mensaje' => 'Cliente actualizado correctamente',
        ]);
    }

    private function reglas(): array
    {
        return [
            'nombre'            => 'required|string|max:191',
            'email'             => 'nullable|email|max:191',
            'ajuste_porcentaje' => 'nullable|numeric|min:-90|max:200',
            'dias_credito'      => 'nullable|integer|min:0|max:180',
        ];
    }

    private function mensajes(): array
    {
        return [
            'ajuste_porcentaje.min' => 'El descuento no puede ser mayor al 90 %.',
            'ajuste_porcentaje.max' => 'El recargo no puede ser mayor al 200 %.',
        ];
    }

    // Convenio (ej: Rappi): % sobre la carta, venta a crédito y código de pedido
    private function convenio(Request $request): array
    {
        $credito = $request->boolean('venta_credito');

        return [
            'ajuste_porcentaje' => round((float) ($request->ajuste_porcentaje ?? 0), 2),
            'venta_credito'     => $credito,
            'dias_credito'      => $credito ? (int) ($request->dias_credito ?? 0) : 0,
            'pide_codigo'       => $request->boolean('pide_codigo'),
        ];
    }
}
