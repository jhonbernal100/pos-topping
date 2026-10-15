@extends('layouts.pos')

@section('titulo', 'Clientes')

@section('contenido')
<div style="padding:16px;max-width:1200px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h1 style="font-size:22px;">👥 Clientes</h1>
        <a href="/clientes/crear"
           style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;">
            + Nuevo cliente
        </a>
    </div>

    <div style="margin-bottom:12px;">
        <input type="text" id="buscador" placeholder="Buscar cliente..."
               style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
    </div>

    <div style="background:#fff;border-radius:12px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Nombre</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Documento</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Teléfono</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Ciudad</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                <tr style="border-bottom:1px solid #eee;" class="fila-cliente"
                    data-nombre="{{ strtolower($cliente->nombre) }}">
                    <td style="padding:12px;font-size:14px;font-weight:bold;">{{ $cliente->nombre }}</td>
                    <td style="padding:12px;font-size:13px;">{{ $cliente->tipo_documento }} {{ $cliente->numero_documento ?? '—' }}</td>
                    <td style="padding:12px;font-size:13px;">{{ $cliente->telefono ?? '—' }}</td>
                    <td style="padding:12px;font-size:13px;">{{ $cliente->ciudad ?? '—' }}</td>
                    <td style="padding:12px;">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="/clientes/{{ $cliente->id }}/editar"
                               style="padding:6px 12px;background:#000;color:#fff;border-radius:6px;font-size:12px;text-decoration:none;">
                                ✏️ Editar
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:24px;text-align:center;color:#999;">
                        No hay clientes. <a href="/clientes/crear" style="color:#000;">Agrega el primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.fila-cliente').forEach(fila => {
        fila.style.display = fila.dataset.nombre.includes(q) ? '' : 'none';
    });
});
</script>
@endsection