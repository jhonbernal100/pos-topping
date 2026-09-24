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

        return response()->view('errors.sin-tenant', [], 403);
    }
}