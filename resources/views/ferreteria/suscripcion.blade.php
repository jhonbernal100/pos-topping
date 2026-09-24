@extends('layouts.pos')

@section('titulo', 'Mi Suscripción')

@section('contenido')
<div style="max-width:600px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;text-align:center;">
        <h2 style="font-size:20px;margin-bottom:20px;">⭐ Estado de suscripción</h2>

        @php $dias = $tenant->diasRestantes(); @endphp

        <div style="font-size:60px;margin-bottom:16px;">
            @if($tenant->subscription_status === 'trial') 🕐
            @elseif($tenant->subscription_status === 'activa') ✅
            @elseif($tenant->subscription_status === 'vencida') ❌
            @else ⛔
            @endif
        </div>

        <div style="font-size:22px;font-weight:bold;margin-bottom:8px;">
            @if($tenant->subscription_status === 'trial') Período de prueba
            @elseif($tenant->subscription_status === 'activa') Suscripción activa
            @elseif($tenant->subscription_status === 'vencida') Suscripción vencida
            @else Cuenta suspendida
            @endif
        </div>

        @if($tenant->subscription_status === 'trial')
        <div style="font-size:16px;color:#555;margin-bottom:20px;">
            Te quedan <strong>{{ $dias }} días</strong> de prueba gratuita
        </div>
        @elseif($tenant->subscription_status === 'activa')
        <div style="font-size:16px;color:#555;margin-bottom:20px;">
            Válida hasta: <strong>{{ $tenant->subscription_ends_at?->format('d/m/Y') }}</strong>
        </div>
        @endif

        <div style="background:#f5f5f5;border-radius:10px;padding:20px;margin-bottom:20px;text-align:left;">
            <div style="font-size:14px;color:#555;margin-bottom:8px;">Negocio: <strong>{{ $tenant->nombre }}</strong></div>
            <div style="font-size:14px;color:#555;margin-bottom:8px;">Plan: <strong>{{ ucfirst($tenant->plan) }}</strong></div>
            <div style="font-size:14px;color:#555;">NIT: <strong>{{ $tenant->nit }}</strong></div>
        </div>

        <a href="https://www.avanzas.digital/index.html" target="_blank"
            style="display:block;padding:16px;background:#000;color:#fff;border-radius:10px;font-size:18px;font-weight:bold;text-decoration:none;">
            📞 Contactar Avanzas Digital
        </a>

        <p style="margin-top:16px;font-size:12px;color:#aaa;">
            Para renovar o actualizar tu plan contacta a nuestro equipo
        </p>
    </div>
</div>
@endsection