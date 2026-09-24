@extends('layouts.pos')

@section('titulo', 'Usuarios')

@section('contenido')
@php
    $actual   = auth()->user();
    $tenant   = $actual->tenant;
    $esTrial  = $tenant?->esTrial();
    $limite   = $tenant?->limiteUsuariosPorSede();

    // Cupos de la demo por sede (solo el propietario ve todas)
    $cupos = $esTrial
        ? \App\Models\Sede::where('tenant_id', $tenant->id)
            ->where('activo', true)
            ->when($actual->esGerenteSede(), fn ($q) => $q->where('id', $actual->sede_id))
            ->withCount('usuarios')
            ->orderBy('nombre')
            ->get()
        : collect();
@endphp

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;">
        <h1 style="font-size:22px;">Usuarios del sistema</h1>
        <a href="/usuarios/crear"
           style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;">
            + Nuevo usuario
        </a>
    </div>

    @if($esTrial && $cupos->isNotEmpty())
        <div style="background:#fff3cd;color:#856404;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;">
            <strong>Demo:</strong> hasta {{ $limite }} usuarios por sede.
            @foreach($cupos as $s)
                &nbsp;·&nbsp; {{ $s->nombre }}: <strong>{{ $s->usuarios_count }}/{{ $limite }}</strong>
            @endforeach
        </div>
    @endif

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:760px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Nombre</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Correo</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Rol</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Sede</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Estado</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                @php
                    $rolTexto = $usuario->esPropietario() ? 'Propietario'
                              : ($usuario->esGerenteSede() ? 'Gerente' : 'Auxiliar');

                    [$rolFondo, $rolColor] = match ($rolTexto) {
                        'Propietario' => ['#99CF8E', '#1f4d17'],
                        'Gerente'     => ['#000', '#fff'],
                        default       => ['#f0f0f0', '#555'],
                    };

                    // Un gerente de sede no puede modificar a otros gerentes
                    $puedeGestionar = $usuario->id !== $actual->id
                        && !($actual->esGerenteSede() && $usuario->rol === 'dueno');
                @endphp
                <tr style="border-bottom:1px solid #eee;" id="fila-usr-{{ $usuario->id }}">
                    <td style="padding:12px;">
                        <div style="font-weight:bold;font-size:14px;">{{ $usuario->name }}</div>
                        @if($usuario->id === $actual->id)
                        <div style="font-size:11px;color:#888;">(tu cuenta)</div>
                        @endif
                    </td>
                    <td style="padding:12px;font-size:13px;">{{ $usuario->email }}</td>
                    <td style="padding:12px;text-align:center;">
                        <span style="padding:4px 12px;border-radius:12px;font-size:11px;font-weight:bold;
                            background:{{ $rolFondo }};color:{{ $rolColor }};">
                            {{ $rolTexto }}
                        </span>
                    </td>
                    <td style="padding:12px;text-align:center;font-size:13px;">
                        {{ $usuario->sede?->nombre ?? 'Todas' }}
                    </td>
                    <td style="padding:12px;text-align:center;" id="estado-{{ $usuario->id }}">
                        <span style="padding:4px 12px;border-radius:12px;font-size:11px;font-weight:bold;
                            background:{{ $usuario->activo ? '#d4edda' : '#f8d7da' }};
                            color:{{ $usuario->activo ? '#155724' : '#721c24' }};">
                            {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td style="padding:12px;text-align:center;">
                        @if($puedeGestionar)
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <a href="/usuarios/{{ $usuario->id }}/editar"
                                style="padding:6px 10px;background:#000;color:#fff;border-radius:6px;font-size:11px;text-decoration:none;">
                                Editar
                            </a>
                            <button onclick="toggleActivo({{ $usuario->id }})"
                                style="padding:6px 10px;background:#856404;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer;">
                                Activar/Desactivar
                            </button>
                            <button onclick='eliminar({{ $usuario->id }}, @json($usuario->name))'
                                style="padding:6px 10px;background:#c00;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer;">
                                Eliminar
                            </button>
                        </div>
                        @elseif($usuario->id === $actual->id)
                        <a href="/usuarios/{{ $usuario->id }}/editar"
                            style="padding:6px 10px;background:#f0f0f0;color:#000;border-radius:6px;font-size:11px;text-decoration:none;">
                            Editar mis datos
                        </a>
                        @else
                        <span style="font-size:12px;color:#aaa;">Sin permisos</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:24px;text-align:center;color:#999;">
                        No hay usuarios. <a href="/usuarios/crear" style="color:#000;">Crea el primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@section('scripts')
<script>
async function toggleActivo(id) {
    const res = await fetch(`/usuarios/${id}/toggle-activo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });

    const data = await res.json();
    if (data.success) {
        const celda = document.getElementById('estado-' + id);
        celda.innerHTML = `<span style="padding:4px 12px;border-radius:12px;font-size:11px;font-weight:bold;
            background:${data.activo ? '#d4edda' : '#f8d7da'};
            color:${data.activo ? '#155724' : '#721c24'};">
            ${data.activo ? 'Activo' : 'Inactivo'}
        </span>`;
    } else {
        alert('Error: ' + (data.mensaje ?? data.message ?? 'No autorizado'));
    }
}

async function eliminar(id, nombre) {
    if (!confirm(`Eliminar al usuario "${nombre}"?`)) return;

    const res = await fetch(`/usuarios/${id}/eliminar`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });

    const data = await res.json();
    if (data.success) {
        document.getElementById('fila-usr-' + id).remove();
    } else {
        alert('Error: ' + (data.mensaje ?? data.message ?? 'No autorizado'));
    }
}
</script>
@endsection
@endsection