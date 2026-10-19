<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Conexión con WhatsApp: cambiar aquí si se pasa a la API oficial de Meta
        $this->app->bind(\App\Services\WhatsApp\WhatsAppGateway::class, \App\Services\WhatsApp\OpenWaGateway::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}