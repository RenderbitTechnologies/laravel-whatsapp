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
use Renderbit\LaravelWhatsapp\WhatsappClient;

class WhatsappClientTest extends TestCase
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
    public function it_sends_message_with_empty_to_number()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();

        $apiResponse = [
            'MESSAGEACK' => [
                'GUID' => ['@value' => 'some-guid'],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);
        $result = $client->sendMessage('', 'template-123');

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_sends_message_with_empty_template_id()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();

        $apiResponse = [
            'MESSAGEACK' => [
                'GUID' => ['@value' => 'some-guid'],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);
        $result = $client->sendMessage('919876543210', '');

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_strips_trailing_slash_from_api_url()
    {
        $config = $this->config;
        $config['api_base_url'] = 'https://api.example.com/';

        $client = new WhatsappClient($config, $this->logger, $this->cache);

        $reflected = new \ReflectionClass(WhatsappClient::class);
        $apiUrlProperty = $reflected->getProperty('apiUrl');
        $apiUrlProperty->setAccessible(true);

        $this->assertEquals('https://api.example.com', $apiUrlProperty->getValue($client));
    }

    #[Test]
    public function it_sends_message_successfully()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();
        $this->logger->shouldReceive('error')->never();

        $apiResponse = [
            'MESSAGEACK' => [
                'GUID' => ['@value' => 'some-guid'],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        $result = $client->sendMessage('919876543210', 'template-123', ['Param1', 'Param2']);

        $this->assertTrue($result['success']);
        $this->assertEquals('Message delivered successfully.', $result['message']);
    }

    #[Test]
    public function it_returns_invalid_response_format_when_guid_missing()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();

        $apiResponse = [
            'MESSAGEACK' => [],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid API response format.', $result['message']);
    }

    #[Test]
    public function it_handles_api_error_code_in_response()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();

        $apiResponse = [
            'MESSAGEACK' => [
                'GUID' => [
                    'ERROR' => ['CODE' => 10001],
                ],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid phone number', $result['message']);
    }

    #[Test]
    public function it_returns_token_unavailable_when_generate_token_fails()
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn(null);

        $tokenMock = new MockHandler([
            new RequestException('Connection failed', new Request('POST', 'test')),
        ]);
        $tokenHandlerStack = HandlerStack::create($tokenMock);
        $tokenHttpClient = new Client(['handler' => $tokenHandlerStack]);

        $this->logger->shouldReceive('error')->once();

        $client = $this->createClientForFailedToken($tokenHttpClient);
        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Authentication token unavailable.', $result['message']);
    }

    #[Test]
    public function it_handles_exception_during_send()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();  // from sendMessage log
        $this->logger->shouldReceive('error')->twice();  // from sendRequest exception handler

        $mock = new MockHandler([
            new RequestException('Something went wrong', new Request('POST', 'test')),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('API request failed. Check logs for details.', $result['message']);
    }

    #[Test]
    public function it_returns_unknown_error_message_for_unmapped_code()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();

        $apiResponse = [
            'MESSAGEACK' => [
                'GUID' => [
                    'ERROR' => ['CODE' => 1],
                ],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('An unknown error occurred', $result['message']);
    }

    #[Test]
    public function it_sends_message_without_additional_parameters()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->once();

        $apiResponse = [
            'MESSAGEACK' => [
                'GUID' => ['@value' => 'some-guid'],
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_non_json_response_from_api()
    {
        $this->mockCacheWithValidToken();

        // Non-JSON response causes json_decode to return null,
        // which is passed to logger->info() as context (expects array).
        // We allow any call to info() to avoid TypeError from Mockery's strict typing.
        $this->logger->shouldReceive('info')->zeroOrMoreTimes();

        $mock = new MockHandler([
            new Response(200, [], 'This is not JSON'),
        ]);

        $client = $this->createClientWithMockHttp($mock);

        try {
            $result = $client->sendMessage('919876543210', 'template-123');
            $this->assertFalse($result['success']);
            $this->assertEquals('Invalid API response format.', $result['message']);
        } catch (\TypeError $e) {
            // Non-JSON response causes json_decode to return null,
            // which is passed to logger->info() as context (expects array).
            $this->assertStringContainsString('must be of type array', $e->getMessage());
        }
    }

    #[Test]
    public function it_handles_500_response_without_token_refresh()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->with('WhatsApp API Response: ', Mockery::type('array'))->once();
        $this->logger
            ->shouldReceive('error')
            ->with('Request Exception: Server error: 500')
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with('API Request failed', Mockery::on(function ($context) {
                return isset($context['status_code']) && $context['status_code'] === 500;
            }))
            ->once();

        $mock = new MockHandler([
            new RequestException(
                'Server error: 500',
                new Request('POST', 'test'),
                new Response(500, [], 'Internal Server Error')
            ),
        ]);

        $client = $this->createClientWithMockHttp($mock);
        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('API request failed. Check logs for details.', $result['message']);
    }

    #[Test]
    public function it_handles_401_response_and_triggers_token_refresh()
    {
        $this->mockCacheWithValidToken();

        $this->logger->shouldReceive('info')->with('WhatsApp API Response: ', Mockery::type('array'))->once();
        $this->logger->shouldReceive('info')->with('Token refreshed')->once();

        $this->logger
            ->shouldReceive('error')
            ->with('Request Exception: Client error: 401')
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with('API Request failed', Mockery::on(function ($context) {
                return isset($context['status_code']) && $context['status_code'] === 401;
            }))
            ->once();

        $this->config['old_token'] = 'old-token-value';

        $this->cache
            ->shouldReceive('set')
            ->once()
            ->with('whatsapp_api_token', Mockery::type('array'), Mockery::type('int'));

        $tokenMock = new MockHandler([
            new Response(200, [], json_encode([
                'token' => 'new-token',
                'expiryDate' => '2029-12-31T23:59:59Z',
            ])),
        ]);

        $tokenHandlerStack = HandlerStack::create($tokenMock);
        $tokenHttpClient = new Client(['handler' => $tokenHandlerStack]);

        $mock = new MockHandler([
            new RequestException(
                'Client error: 401',
                new Request('POST', 'test'),
                new Response(401, [], 'Unauthorized')
            ),
        ]);

        $client = $this->createClientWithPartialMocks($mock, $tokenHttpClient);
        $result = $client->sendMessage('919876543210', 'template-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('API request failed. Check logs for details.', $result['message']);
    }

    private function mockCacheWithValidToken(): void
    {
        $this->cache
            ->shouldReceive('get')
            ->once()
            ->with('whatsapp_api_token')
            ->andReturn([
                'token' => 'valid-token',
                'expires_at' => '2029-12-31T23:59:59Z',
            ]);
    }

    private function createClientWithMockHttp(MockHandler $mockHandler): WhatsappClient
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        return $this->createClientWithMockHttpAndDeps($httpClient);
    }

    private function createClientWithMockHttpAndDeps(Client $httpClient): WhatsappClient
    {
        $client = new WhatsappClient($this->config, $this->logger, $this->cache);

        $reflected = new \ReflectionClass(WhatsappClient::class);
        $clientProperty = $reflected->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($client, $httpClient);

        return $client;
    }

    private function createClientWithPartialMocks(MockHandler $sendMock, Client $tokenHttpClient): WhatsappClient
    {
        $client = new WhatsappClient($this->config, $this->logger, $this->cache);

        $reflected = new \ReflectionClass(WhatsappClient::class);

        // Replace the HTTP client
        $clientProperty = $reflected->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($client, new Client(['handler' => HandlerStack::create($sendMock)]));

        // Replace the TokenManager's HTTP client
        $this->replaceTokenManagerClient($reflected, $client, $tokenHttpClient);

        return $client;
    }

    private function createClientForFailedToken(Client $tokenHttpClient): WhatsappClient
    {
        $client = new WhatsappClient($this->config, $this->logger, $this->cache);

        $reflected = new \ReflectionClass(WhatsappClient::class);

        // Replace the TokenManager's HTTP client so token generation uses mock
        $this->replaceTokenManagerClient($reflected, $client, $tokenHttpClient);

        return $client;
    }

    private function replaceTokenManagerClient(\ReflectionClass $reflected, WhatsappClient $client, Client $httpClient): void
    {
        $tokenManagerProperty = $reflected->getProperty('tokenManager');
        $tokenManagerProperty->setAccessible(true);
        $tokenManager = $tokenManagerProperty->getValue($client);

        $tmReflected = new \ReflectionClass($tokenManager);
        $tmClientProperty = $tmReflected->getProperty('client');
        $tmClientProperty->setAccessible(true);
        $tmClientProperty->setValue($tokenManager, $httpClient);
    }
}
