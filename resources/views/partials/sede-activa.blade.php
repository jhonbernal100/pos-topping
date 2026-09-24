@php
    $u          = auth()->user();
    $sedeActiva = session('sede_id') ? \App\Models\Sede::find(session('sede_id')) : null;
    $sedesProp  = $u && $u->esPropietario()
        ? \App\Models\Sede::whereIn('id', $u->sedesPermitidasIds())->orderBy('nombre')->get()
        : collect();
@endphp

@if($u && !$u->esSuperAdmin())
    @if($sedesProp->count() > 1)
        <form method="POST" action="/sede-activa" style="margin:4px 0 0;">
            @csrf
            <select name="sede_id" onchange="this.form.submit()" aria-label="Sede activa"
                style="background:#222;color:#99CF8E;border:1px solid #444;border-radius:6px;padding:4px 8px;font-size:12px;font-weight:bold;cursor:pointer;">
                @foreach($sedesProp as $s)
                    <option value="{{ $s->id }}" {{ $sedeActiva?->id === $s->id ? 'selected' : '' }}>
                        Sede {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </form>
    @else
        <span style="color:#99CF8E;font-size:12px;font-weight:bold;margin-top:2px;">
            Sede {{ $sedeActiva?->nombre ?? 'sin asignar' }}
        </span>
    @endif
@endif