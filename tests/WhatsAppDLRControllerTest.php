<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Renderbit\LaravelWhatsapp\Http\Controllers\WhatsAppDLRController;

class WhatsAppDLRControllerTest extends LaravelTestCase
{
    /** @test */
    public function it_returns_success_response_for_dlr()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('DLR Received:', ['status' => 'delivered', 'msgId' => 'abc123']);

        $controller = new WhatsAppDLRController();
        $request = Request::create('/whatsapp/dlr', 'POST', [
            'status' => 'delivered',
            'msgId' => 'abc123',
        ]);

        $response = $controller->receiveDLR($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['status' => 'success'], $response->getData(true));
    }

    /** @test */
    public function it_logs_empty_request_data()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('DLR Received:', []);

        $controller = new WhatsAppDLRController();
        $request = Request::create('/whatsapp/dlr', 'POST');

        $response = $controller->receiveDLR($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['status' => 'success'], $response->getData(true));
    }
}
