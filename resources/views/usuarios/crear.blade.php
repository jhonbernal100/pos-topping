@extends('layouts.pos')

@section('titulo', 'Nuevo Usuario')

@section('contenido')
@php
    $actual       = auth()->user();
    $esGerente    = $actual->esGerenteSede();
    $sedeGerente  = $esGerente ? $sedes->first() : null;
@endphp

<div style="max-width:520px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;">
        <h2 style="font-size:20px;margin-bottom:6px;">Nuevo usuario</h2>
        <p style="font-size:13px;color:#888;margin-bottom:20px;">
            El usuario recibira sus credenciales de acceso por correo electronico.
        </p>

        <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

        @if($sedes->isEmpty())
            <div style="padding:12px;border-radius:8px;background:#fff3cd;color:#856404;font-size:14px;margin-bottom:16px;">
                Aun no hay sedes registradas. Crea al menos una sede antes de agregar usuarios.
            </div>
        @endif

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre completo *</label>
            <input type="text" id="f-name" placeholder="Ej: Maria Gonzalez"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Correo electronico *</label>
            <input type="email" id="f-email" placeholder="correo@ejemplo.com"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        @if($esGerente)
            {{-- El gerente de sede solo crea auxiliares de su propia sede --}}
            <input type="hidden" id="f-rol" value="auxiliar">
            <input type="hidden" id="f-sede" value="{{ $sedeGerente?->id }}">

            <div style="padding:12px;border-radius:8px;background:#f5f5f5;font-size:14px;margin-bottom:20px;">
                Se creara un <strong>auxiliar</strong> para la sede
                <strong>{{ $sedeGerente?->nombre }}</strong>.
            </div>
        @else
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Rol *</label>
                <select