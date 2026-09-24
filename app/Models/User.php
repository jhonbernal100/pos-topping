<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'sede_id',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'activo'            => 'boolean',
    ];

    // Solo superadmin accede al panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->rol === 'superadmin' && $this->activo;
    }

    public function esSuperAdmin(): bool { return $this->rol === 'superadmin'; }
    public function esDueno(): bool      { return $this->rol === 'dueno'; }
    public function esAuxiliar(): bool   { return $this->rol === 'auxiliar'; }

    // Dueño sin sede = propietario del negocio, ve todas las sedes
    public function esPropietario(): bool
    {
        return $this->rol === 'dueno' && is_null($this->sede_id);
    }

    // Dueño con sede = gerente de ese punto de venta
    public function esGerenteSede(): bool
    {
        return $this->rol === 'dueno' && !is_null($this->sede_id);
    }

    // IDs de sedes que este usuario puede ver y operar
    public function sedesPermitidasIds(): array
    {
        if ($this->esPropietario()) {
            return Sede::where('tenant_id', $this->tenant_id)
                ->where('activo', true)
                ->pluck('id')
                ->all();
        }

        return $this->sede_id ? [$this->sede_id] : [];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
}