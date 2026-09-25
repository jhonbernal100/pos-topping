<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\FerreteriaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PlanesController;
use App\Http\Controllers\SedeActivaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MensajeController;
use App\Http\Middleware\EnsureSuperAdmin;

Route::get('/', fn() => redirect('/login'));

Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Si el navegador llega a /logout por GET (recarga, botón atrás), cerrar sesión y enviar al login
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
});

// Rutas públicas — registro trial
Route::prefix('trial')->group(function () {
    Route::get('/',                 [TrialController::class, 'index'])->name('trial.index');
    Route::post('/procesar-rut',    [TrialController::class, 'procesarRut'])->name('trial.procesarRut');
    Route::post('/confirmar',       [TrialController::class, 'confirmar'])->name('trial.confirmar');
    Route::post('/verificar',       [TrialController::class, 'verificar'])->name('trial.verificar');
    Route::post('/reenviar-codigo', [TrialController::class, 'reenviarCodigo'])->name('trial.reenviarCodigo');
    Route::get('/paso2',            fn() => view('trial.paso2'))->name('trial.paso2');
    Route::get('/paso3',            fn() => view('trial.paso3'))->name('trial.paso3');
});

Route::get('/planes', [PlanesController::class, 'index'])->name('planes.index');

Route::middleware('auth')->group(function () {
    Route::get('/mensajes/no-leidos',  [MensajeController::class, 'noLeidos'])->name('mensajes.noLeidos');
    Route::post('/mensajes/leer',      [MensajeController::class, 'marcarLeido'])->name('mensajes.leer');

    // Cambio de sede activa (solo propietario)
    Route::post('/sede-activa', [SedeActivaController::class, 'cambiar'])->name('sede.cambiar');

    Route::prefix('ventas')->group(function () {
        Route::get('/',                     [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/crear',                [VentaController::class, 'crear'])->name('ventas.crear');
        Route::post('/',                    [VentaController::class, 'store'])->name('ventas.store');
        Route::get('/{venta}/ticket',       [VentaController::class, 'ticket'])->name('ventas.ticket');
        Route::post('/{venta}/anular',      [VentaController::class, 'anular'])->name('ventas.anular');
        Route::get('/{venta}/ticket-abono', [VentaController::class, 'ticketAbono'])->name('ventas.ticketAbono');
        Route::get('/{venta}/devolucion',   [DevolucionController::class, 'index'])->name('ventas.devolucion');
        Route::post('/{venta}/devolucion',  [DevolucionController::class, 'procesar'])->name('ventas.procesarDevolucion');
    });

    Route::prefix('inventario')->group(function () {
        Route::get('/',                                [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('/capturar',                        [InventarioController::class, 'capturar'])->name('inventario.capturar');
        Route::post('/analizar',                       [InventarioController::class, 'analizar'])->name('inventario.analizar');
        Route::post('/guardar',                        [InventarioController::class, 'guardar'])->name('inventario.guardar');
        Route::get('/crear-manual',                    [InventarioController::class, 'crear'])->name('inventario.crear');
        Route::get('/{producto}/editar',               [InventarioController::class, 'editar'])->name('inventario.editar');
        Route::post('/{producto}/actualizar',          [InventarioController::class, 'actualizar'])->name('inventario.actualizar');
        Route::post('/{producto}/actualizar-producto', [InventarioController::class, 'actualizar_producto'])->name('inventario.actualizarProducto');
        Route::delete('/{producto}/eliminar',          [InventarioController::class, 'eliminar'])->name('inventario.eliminar');
    });

    Route::prefix('ferreteria')->group(function () {
        Route::get('/perfil',      [FerreteriaController::class, 'perfil'])->name('ferreteria.perfil');
        Route::post('/perfil',     [FerreteriaController::class, 'actualizarPerfil'])->name('ferreteria.actualizar');
        Route::get('/suscripcion', [FerreteriaController::class, 'suscripcion'])->name('ferreteria.suscripcion');
    });

    Route::prefix('proveedores')->group(function () {
        Route::get('/',                       [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('/crear',                  [ProveedorController::class, 'crear'])->name('proveedores.crear');
        Route::post('/',                      [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::get('/{proveedor}/editar',     [ProveedorController::class, 'editar'])->name('proveedores.editar');
        Route::post('/{proveedor}/actualizar',[ProveedorController::class, 'actualizar'])->name('proveedores.actualizar');
    });

    Route::prefix('clientes')->group(function () {
        Route::get('/',                         [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/crear',                    [ClienteController::class, 'crear'])->name('clientes.crear');
        Route::post('/',                        [ClienteController::class, 'store'])->name('clientes.store');
        Route::get('/{cliente}/creditos',       [ClienteController::class, 'creditos'])->name('clientes.creditos');
        Route::post('/{cliente}/creditos',      [ClienteController::class, 'guardarCredito'])->name('clientes.guardarCredito');
        Route::get('/{cliente}/historial',      [ClienteController::class, 'historialCredito'])->name('clientes.historial');
        Route::post('/{cliente}/pagar-credito', [ClienteController::class, 'pagarCredito'])->name('clientes.pagarCredito');
        Route::get('/{cliente}/editar',         [ClienteController::class, 'editar'])->name('clientes.editar');
        Route::post('/{cliente}/actualizar',    [ClienteController::class, 'actualizar'])->name('clientes.actualizar');
    });

    Route::prefix('gastos')->group(function () {
        Route::get('/',                    [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/crear',               [GastoController::class, 'crear'])->name('gastos.crear');
        Route::post('/',                   [GastoController::class, 'store'])->name('gastos.store');
        Route::delete('/{gasto}/eliminar', [GastoController::class, 'eliminar'])->name('gastos.eliminar');
    });

    Route::prefix('reportes')->group(function () {
        Route::get('/',                  [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/inventario',        [ReporteController::class, 'inventario'])->name('reportes.inventario');
        Route::get('/stock-bajo',        [ReporteController::class, 'stockBajo'])->name('reportes.stockBajo');
        Route::get('/ventas-dia',        [ReporteController::class, 'ventasDia'])->name('reportes.ventasDia');
        Route::get('/ventas-semana',     [ReporteController::class, 'ventasSemana'])->name('reportes.ventasSemana');
        Route::get('/ventas-mes',        [ReporteController::class, 'ventasMes'])->name('reportes.ventasMes');
        Route::get('/kardex',            [ReporteController::class, 'kardex'])->name('reportes.kardex');
        Route::get('/creditos',          [ReporteController::class, 'creditos'])->name('reportes.creditos');
        Route::get('/estado-financiero', [ReporteController::class, 'estadoFinanciero'])->name('reportes.estadoFinanciero');
    });

    Route::prefix('usuarios')->group(function () {
        Route::get('/',                         [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/crear',                    [UsuarioController::class, 'crear'])->name('usuarios.crear');
        Route::post('/',                        [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/{usuario}/editar',         [UsuarioController::class, 'editar'])->name('usuarios.editar');
        Route::post('/{usuario}/actualizar',    [UsuarioController::class, 'actualizar'])->name('usuarios.actualizar');
        Route::post('/{usuario}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('usuarios.toggleActivo');
        Route::delete('/{usuario}/eliminar',    [UsuarioController::class, 'eliminar'])->name('usuarios.eliminar');
    });

    // Menú (solo propietario — validado en MenuController)
    Route::prefix('menu')->group(function () {
        Route::get('/',                                    [MenuController::class, 'index'])->name('menu.index');
        Route::post('/productos/{producto}/toggle',        [MenuController::class, 'toggleProducto'])->name('menu.toggleProducto');
        Route::post('/modificadores/{modificador}/toggle', [MenuController::class, 'toggleModificador'])->name('menu.toggleModificador');
    });

    // Panel Avanzas Digital — solo superadmin
    Route::prefix('admin')->middleware(EnsureSuperAdmin::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/ferreterias/{tenant}', [DashboardController::class, 'ferreteria'])->name('admin.ferreteria');
        Route::post('/ferreterias/{tenant}/ampliar-trial', [DashboardController::class, 'ampliarTrial'])->name('admin.ampliarTrial');
        Route::post('/ferreterias/{tenant}/cambiar-plan', [DashboardController::class, 'cambiarPlan'])->name('admin.cambiarPlan');
        Route::delete('/ferreterias/{tenant}/eliminar', [DashboardController::class, 'eliminarFerreteria'])->name('admin.eliminarFerreteria');
        Route::post('/mensajes/enviar',        [MensajeController::class, 'enviar'])->name('admin.mensajes.enviar');
        Route::post('/mensajes/masivo',        [MensajeController::class, 'enviarMasivo'])->name('admin.mensajes.masivo');
    });
});