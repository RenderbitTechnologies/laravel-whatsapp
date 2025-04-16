<?php

use Illuminate\Support\Facades\Route;
use Renderbit\LaravelWhatsApp\Http\Controllers\WhatsAppDLRController;

Route::post('/whatsapp/dlr', [WhatsAppDLRController::class, 'receiveDLR']);

