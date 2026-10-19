<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\OpenWaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// WhatsApp (OpenWA) → bot de domicilios. Autenticado con firma HMAC (OPENWA_WEBHOOK_SECRET).
Route::post('/webhooks/openwa', OpenWaController::class)->middleware('throttle:120,1');
