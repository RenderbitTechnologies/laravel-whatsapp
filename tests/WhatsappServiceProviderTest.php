<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use PHPUnit\Framework\Attributes\Test;
use Renderbit\LaravelWhatsapp\WhatsappClient;
use Renderbit\LaravelWhatsapp\WhatsappServiceProvider;

class WhatsappServiceProviderTest extends LaravelTestCase
{
    #[Test]
    public function it_merges_default_config()
    {
        $this->assertIsArray(config('whatsapp'));
        $this->assertEquals('https://api.example.com', config('whatsapp.api_base_url'));
    }

    #[Test]
    public function it_binds_whatsapp_client_as_singleton()
    {
        $client1 = $this->app->make(WhatsappClient::class);
        $client2 = $this->app->make(WhatsappClient::class);

        $this->assertInstanceOf(WhatsappClient::class, $client1);
        $this->assertSame($client1, $client2);
    }

    #[Test]
    public function it_registers_whatsapp_alias()
    {
        $this->assertTrue($this->app->bound('whatsapp'));
        $this->assertInstanceOf(
            WhatsappClient::class,
            $this->app->make('whatsapp')
        );
    }
}
