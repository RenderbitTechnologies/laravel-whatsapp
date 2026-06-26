<?php

use Illuminate\Support\Facades\Route;
use Renderbit\LaravelWhatsapp\Http\Controllers\WhatsAppDLRController;

Route::post('/whatsapp/dlr', [WhatsAppDLRController::class, 'receiveDLR']);

