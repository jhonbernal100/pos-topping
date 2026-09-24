@extends('layouts.pos')

@section('titulo', 'Editar Usuario')

@section('contenido')
@php
    $actual      = auth()->user();
    $esGerente   = $actual->esGerenteSede();
    $esUnoMismo  = $usuario->id === $actual->id;
    $bloquearRol = $esGerente || $esUnoMismo;
@endphp

<div style="max-width:520px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;">
        <h2 style="font-size:20px;margin-bottom:6px;">Editar usuario</h2>
        <p style="font-size:13px;color:#888;margin-bottom:20px;">
            Deja la contrasena en blanco para no cambiarla.
        </p>

        <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre completo *</label>
            <input type="text" id="f-name" value="{{ $usuario->name }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Correo electronico *</label>
            <input type="email" id="f-email" value="{{ $usuario->email }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        @if($bloquearRol)
            <input type="hidden" id="f-rol" value="{{ $usuario->rol }}">
            <input type="hidden" id="f-sede" value="{{ $usuario->sede_id }}">

            <div style="padding:12px;border-radius:8px;background:#f5f5f5;font-size:14px;margin-bottom:14px;">
                <strong>Rol:</strong>
                @if($usuario->esPropietario()) Propietario — todas las sedes
                @elseif($usuario->esGerenteSede()) Gerente
                @else Auxiliar
                @endif
                <br>
                <strong>Sede:</strong> {{ $usuario->sede?->nombre ?? 'Todas las sedes' }}
                @if($esUnoMismo)
                    <div style="font-size:11px;color:#888;margin-top:6px;">
                        No puedes cambiar tu propio rol ni tu sede.
                    </div>
                @endif
            </div>
        @else
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Rol *</label>
                <select id="f-rol" onchange="actualizarSedes()"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="auxiliar" {{ $usuario->rol === 'auxiliar' ? 'selected' : '' }}>
                        Auxiliar — vende y registra inventario
                    </option>
                    <option value="dueno" {{ $usuario->rol === 'dueno' ? 'selected' : '' }}>
                        Gerente — acceso completo
                    </option>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Sede *</label>
                <select id="f-sede"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="" id="opt-todas" {{ is_null($usuario->sede_id) ? 'selected' : '' }}>
                        Todas las sedes (propietario)
                    </option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ (int) $usuario->sede_id === $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }} — {{ $sede->direccion }}
                        </option>
                    @endforeach
                </select>
                <div id="ayuda-rol" style="font-size:11px;color:#888;margin-top:4px;"></div>
            </div>
        @endif

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">
                Nueva contrasena (opcional)
            </label>
            <input type="password" id="f-password" placeholder="Minimo 8 caracteres"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            <div style="font-size:11px;color:#888;margin-top:3px;">
                Deja en blanco para mantener la contrasena actual
            </div>
        </div>