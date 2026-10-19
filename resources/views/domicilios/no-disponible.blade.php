@extends('layouts.pos')

@section('titulo', 'Domicilios')

@section('contenido')
<div style="max-width:560px;margin:40px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:14px;padding:28px;text-align:center;">
        <div style="font-size:48px;">🛵</div>
        <h1 style="font-size:20px;margin:10px 0;">Domicilios por WhatsApp</h1>
        <p style="color:#555;line-height:1.6;">
            Recibe pedidos a domicilio por WhatsApp con menú con fotos, pago por Nequi o Daviplata
            y avisos automáticos de "en preparación" y "salió tu domicilio".
        </p>
        <p style="color:#555;margin-top:10px;">Está incluido en el <strong>plan anual</strong>.</p>
        <a href="/planes" target="_blank" style="display:inline-block;margin-top:16px;padding:12px 20px;background:#000;color:#fff;border-radius:10px;text-decoration:none;">Ver planes</a>
    </div>
</div>
@endsection
