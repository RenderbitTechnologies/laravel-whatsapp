<?php

namespace Renderbit\LaravelWhatsApp\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppDLRController extends Controller
{
    public function receiveDLR(Request $request)
    {
        Log::info('DLR Received:', $request->all());

        return response()->json(['status' => 'success']);
    }
}
