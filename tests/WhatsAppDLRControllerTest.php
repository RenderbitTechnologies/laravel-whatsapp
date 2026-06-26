<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Renderbit\LaravelWhatsapp\Http\Controllers\WhatsAppDLRController;

class WhatsAppDLRControllerTest extends LaravelTestCase
{
    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_handles_dlr_request_via_route()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('DLR Received:', Mockery::type('array'));

        $response = $this->postJson('/whatsapp/dlr', [
            'status' => 'delivered',
            'msgId' => 'route-test-123',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }
}
