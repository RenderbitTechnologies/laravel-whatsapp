<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use PHPUnit\Framework\Attributes\Test;
use Renderbit\LaravelWhatsapp\Facades\Whatsapp;

class WhatsappFacadeTest extends LaravelTestCase
{
    #[Test]
    public function facade_accessor_is_whatsapp()
    {
        $reflection = new \ReflectionMethod(Whatsapp::class, 'getFacadeAccessor');
        $reflection->setAccessible(true);
        $accessor = $reflection->invoke(null);

        $this->assertEquals('whatsapp', $accessor);
    }

    #[Test]
    public function facade_resolves_whatsapp_client()
    {
        $this->assertInstanceOf(
            \Renderbit\LaravelWhatsapp\WhatsappClient::class,
            Whatsapp::getFacadeRoot()
        );
    }
}
