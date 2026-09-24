<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Rutas públicas y de acceso que no requieren tenant
        if (
            $request->is('admin*') ||
            $request->is('trial*') ||
            $request->is('planes*') ||
            $request->is('login') ||
            $request->is('logout')
        ) {
            return $next($request);
        }

        if (auth()->check()) {
            $user = auth()->user();

            // Superadmin — solo puede acceder al panel admin
            if ($user->rol === 'superadmin') {
                // Si intenta acceder a rutas del POS lo redirigimos al dashboard
                if (!$request->is('admin*') && !$request->is('logout')) {
                    return redirect('/admin/dashboard');
                }
                config(['app.current_tenant_id' => null]);
                return $next($request);
            }

            // Usuario con tenant asignado
            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant && $tenant->activo && $tenant->tieneAcceso()) {
                    session(['tenant_id' => $tenant->id]);
                    config(['app.current_tenant_id' => $tenant->id]);
                    $this->resolverSedeActiva($user);
                    return $next($request);
                }
            }

            // Usuario autenticado sin acceso
            return response()->view('errors.suscripcion-vencida', [
                'tenant' => $user->tenant ?? new Tenant(['nombre' => 'tu negocio'])
            ], 403);
        }

        // Sin autenticación — buscar primer tenant activo
        $tenant = Tenant::where('activo', true)->first();
        if ($tenant) {
            session(['tenant_id' => $tenant->id]);
            config(['app.current_tenant_id' => $tenant->id]);
            return $next($request);
        }

        // Sin tenants registrados — dejar que auth redirija al login
        return $next($request);
    }

    // Fija la sede con la que trabaja el usuario en esta sesión
    private function resolverSedeActiva($user): void
    {
        // Gerentes y auxiliares: siempre su propia sede
        if ($user->sede_id) {
            session(['sede_id' => (int) $user->sede_id]);
            config(['app.current_sede_id' => (int) $user->sede_id]);
            return;
        }

        // Propietario: conserva la sede elegida si sigue siendo válida
        $permitidas = array_map('intval', $user->sedesPermitidasIds());
        $actual     = (int) session('sede_id');

        if (!in_array($actual, $permitidas, true)) {
            $actual = $permitidas[0] ?? 0;
            session(['sede_id' => $actual ?: null]);
        }

        config(['app.current_sede_id' => $actual ?: null]);
    }
}