<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class LaravelTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Renderbit\LaravelWhatsapp\WhatsappServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('whatsapp', [
            'api_base_url' => 'https://api.example.com',
            'api_key' => 'test-api-key',
            'whatsapp_business_number' => '918888888888',
            'whatsapp_username' => 'testuser',
            'old_token' => null,
        ]);
    }
}
