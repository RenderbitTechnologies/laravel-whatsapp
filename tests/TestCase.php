<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    protected array $defaultConfig = [
        'api_base_url' => 'https://api.example.com',
        'api_key' => 'test-api-key',
        'whatsapp_business_number' => '918888888888',
        'whatsapp_username' => 'testuser',
        'old_token' => null,
    ];

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
