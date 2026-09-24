<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venta;
use App\Models\TrialRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
{
    $totalFerreterias    = Tenant::count();
    $ferreteriasTrial    = Tenant::where('subscription_status', 'trial')->count();
    $ferreteriasActivas  = Tenant::where('subscription_status', 'activa')->count();
    $ferreteriasVencidas = Tenant::where('subscription_status', 'vencida')->count();
    $totalUsuarios       = User::where('rol', '!=', 'superadmin')->count();

    // Ingresos reales por suscripciones del mes actual
    $ingresosSuscripciones = Tenant::where('subscription_status', 'activa')
    ->whereYear('updated_at', now()->year)
    ->get()
    ->sum(function ($tenant) {
    return match($tenant->subscription_plan) {
        'anual'      => 420000,  // $35,000 x 12 meses
        'trimestral' => 135000,  // $45,000 x 3 meses
        default      => 0,
    };
    });

    $trialsProximosVencer = Tenant::where('subscription_status', 'trial')
        ->whereNotNull('trial_ends_at')
        ->where('trial_ends_at', '>=', now())
        ->where('trial_ends_at', '<=', now()->addDays(7))
        ->orderBy('trial_ends_at')
        ->get();

    $ferreteriasRecientes = Tenant::with('usuarios')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();

    $trialsPendientes = TrialRequest::where('estado', 'pendiente')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();

    $ferreterias = Tenant::with('usuarios')
        ->orderByDesc('created_at')
        ->get()
        ->map(function ($tenant) {
            $tenant->dias_restantes = null;
            $tenant->total_skus = \App\Models\Producto::where('tenant_id', $tenant->id)
                ->where('activo', true)
                ->count();

            if ($tenant->subscription_status === 'trial' && $tenant->trial_ends_at) {
                $tenant->dias_restantes = max(0, now()->diffInDays($tenant->trial_ends_at, false));
            } elseif ($tenant->subscription_status === 'activa' && $tenant->subscription_ends_at) {
                $tenant->dias_restantes = max(0, now()->diffInDays($tenant->subscription_ends_at, false));
            }

            return $tenant;
        });

    return view('admin.dashboard', compact(
        'totalFerreterias', 'ferreteriasTrial', 'ferreteriasActivas',
        'ferreteriasVencidas', 'totalUsuarios', 'trialsProximosVencer',
        'ferreteriasRecientes', 'trialsPendientes', 'ingresosSuscripciones', 'ferreterias'
    ));
    }

    public function ferreteria(Tenant $tenant)
    {
        $tenant->load('usuarios');

        $ventas = Venta::where('tenant_id', $tenant->id)
            ->where('estado', 'completada')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalVentas    = Venta::where('tenant_id', $tenant->id)->where('estado', 'completada')->sum('total');
        $totalProductos = \App\Models\Producto::where('tenant_id', $tenant->id)->count();

        return view('admin.ferreteria', compact('tenant', 'ventas', 'totalVentas', 'totalProductos'));
    }

    public function ampliarTrial(Request $request, Tenant $tenant)
    {
        $dias = (int)($request->dias ?? 15);

        if ($tenant->subscription_status === 'trial' && $tenant->trial_ends_at) {
            $nuevaFecha = Carbon::parse($tenant->trial_ends_at)->addDays($dias);
        } else {
            $nuevaFecha = now()->addDays($dias);
        }

        $tenant->update([
            'subscription_status' => 'trial',
            'trial_ends_at'       => $nuevaFecha,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => "Trial ampliado por {$dias} dias hasta " . $nuevaFecha->format('d/m/Y'),
        ]);
    }

    public function cambiarPlan(Request $request, Tenant $tenant)
    {
        $request->validate([
            'plan'  => 'required|in:trial,activa,vencida,suspendida',
            'meses' => 'nullable|integer|min:1',
        ]);

        $data = [
            'subscription_status' => $request->plan,
            'subscription_plan'   => $request->plan === 'activa'
                ? ($request->meses == 12 ? 'anual' : 'trimestral')
                : null,
        ];

        if ($request->plan === 'activa' && $request->meses) {
            $data['subscription_ends_at'] = now()->addMonths((int)$request->meses);
        }

        if ($request->plan === 'trial') {
            $data['trial_ends_at']        = now()->addDays(30);
            $data['subscription_ends_at'] = null;
        }

        if (in_array($request->plan, ['vencida', 'suspendida'])) {
            $data['subscription_ends_at'] = null;
        }

        $tenant->update($data);

        return response()->json([
            'success' => true,
            'mensaje' => 'Plan actualizado: ' . strtoupper($request->plan) .
                         ($request->meses ? " por {$request->meses} meses" : ''),
        ]);
    }

    public function eliminarFerreteria(Request $request, Tenant $tenant)
    {
        User::where('tenant_id', $tenant->id)->delete();
        $tenant->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Negocio eliminado correctamente',
        ]);
    }
}