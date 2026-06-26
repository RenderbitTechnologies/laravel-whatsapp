<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Renderbit\LaravelWhatsapp\TokenManager;

class TokenManagerTest extends TestCase
{
    private LoggerInterface&MockInterface $logger;
    private CacheInterface&MockInterface $cache;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->cache = Mockery::mock(CacheInterface::class);
        $this->config = $this->defaultConfig;
    }

    #[Test]
    public function it_generates_new_token_when_cached_token_is_expired()
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn([
                'token' => 'expired-token',
                'expires_at' => '2020-01-01T00:00:00Z',
            ]);

        $this->cache
            ->shouldReceive('set')
            ->once()
            ->with('whatsapp_api_token', Mockery::type('array'), Mockery::type('int'));

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'token' => 'newly-generated-token',
                'expiryDate' => '2029-12-31T23:59:59Z',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $token = $tokenManager->getToken();

        $this->assertEquals('newly-generated-token', $token);
    }

    #[Test]
    public function it_handles_token_with_past_expiry_date()
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn(null);

        $this->cache
            ->shouldReceive('set')
            ->once()
            ->with('whatsapp_api_token', Mockery::type('array'), 0);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'token' => 'past-expiry-token',
                'expiryDate' => '2020-01-01T00:00:00Z',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $token = $tokenManager->getToken();

        $this->assertEquals('past-expiry-token', $token);
    }

    #[Test]
    public function it_returns_cached_token_when_not_expired()
    {
        $futureTime = '2029-12-31T23:59:59Z';

        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn([
                'token' => 'cached-token-value',
                'expires_at' => $futureTime,
            ]);

        $tokenManager = new TokenManager($this->config, $this->logger, $this->cache);
        $token = $tokenManager->getToken();

        $this->assertEquals('cached-token-value', $token);
    }

    #[Test]
    public function it_generates_new_token_when_cache_is_empty()
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn(null);

        $this->cache
            ->shouldReceive('set')
            ->once()
            ->with('whatsapp_api_token', Mockery::type('array'), Mockery::type('int'));

        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('error')->never();

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'token' => 'newly-generated-token',
                'expiryDate' => '2029-12-31T23:59:59Z',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $token = $tokenManager->getToken();

        $this->assertEquals('newly-generated-token', $token);
    }

    #[Test]
    public function it_generates_token_with_old_token_when_provided()
    {
        $this->config['old_token'] = 'old-token-value';

        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn(null);

        $this->cache
            ->shouldReceive('set')
            ->once()
            ->with('whatsapp_api_token', Mockery::type('array'), Mockery::type('int'));

        $this->logger->shouldReceive('info')->never();
        $this->logger->shouldReceive('error')->never();

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'token' => 'refreshed-token',
                'expiryDate' => '2029-12-31T23:59:59Z',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $token = $tokenManager->getToken();

        $this->assertEquals('refreshed-token', $token);
    }

    #[Test]
    public function it_refreshes_token_successfully()
    {
        $this->config['old_token'] = 'old-token-value';

        $this->cache
            ->shouldReceive('set')
            ->once()
            ->with('whatsapp_api_token', Mockery::type('array'), Mockery::type('int'));

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Token refreshed');

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'token' => 'refreshed-token',
                'expiryDate' => '2029-12-31T23:59:59Z',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $result = $tokenManager->refreshToken();

        $this->assertEquals('Token refreshed', $result);
    }

    #[Test]
    public function it_fails_to_refresh_token_when_old_token_is_null()
    {
        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Old Token Error: Token could not be refreshed');

        $tokenManager = new TokenManager($this->config, $this->logger, $this->cache);
        $result = $tokenManager->refreshToken();

        $this->assertEquals('Old Token Error: Token could not be refreshed', $result);
    }

    #[Test]
    public function it_returns_null_on_token_generation_failure()
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn(null);

        $this->logger->shouldReceive('error')->once();

        $mock = new MockHandler([
            new RequestException('Error Communicating with Server', new Request('POST', 'test')),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $token = $tokenManager->getToken();

        $this->assertNull($token);
    }

    #[Test]
    public function it_returns_null_when_token_response_missing_token_key()
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn(null);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Token generation response missing required keys.');

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'some_other_key' => 'value',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $token = $tokenManager->getToken();

        $this->assertNull($token);
    }

    #[Test]
    public function it_validates_manage_token_action()
    {
        $tokenManager = new TokenManager($this->config, $this->logger, $this->cache);

        $result = $tokenManager->manageToken('invalid', 'some-token');

        $this->assertEquals(['error' => 'Invalid token action.'], $result);
    }

    #[Test]
    public function it_enables_token_successfully()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'enabled'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $result = $tokenManager->manageToken('enable', 'some-token');

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function it_disables_token_successfully()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'disabled'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $result = $tokenManager->manageToken('disable', 'some-token');

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function it_deletes_token_successfully()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'deleted'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $tokenManager = $this->getTokenManagerWithMockClient($client);
        $result = $tokenManager->manageToken('delete', 'some-token');

        $this->assertEquals(200, $result->getStatusCode());
    }

    private function getTokenManagerWithMockClient(Client $client): TokenManager
    {
        $reflected = new \ReflectionClass(TokenManager::class);
        $instance = new TokenManager($this->config, $this->logger, $this->cache);

        $clientProperty = $reflected->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($instance, $client);

        return $instance;
    }
}
