<?php

namespace Renderbit\LaravelWhatsapp;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class TokenManager
{
    protected Client $client;
    protected string $apiUrl;
    protected string $apiKey;
    protected ?string $oldToken;
    protected string $cacheKey = 'whatsapp_api_token';
    protected LoggerInterface $logger;
    protected CacheInterface $cache;

    public function __construct(array $config, LoggerInterface $logger, CacheInterface $cache)
    {
        $this->apiUrl = $config['api_base_url'];
        $this->apiKey = $config['api_key'];
        $this->oldToken = $config['old_token'];

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
        ]);

        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function getToken()
    {
        $cached = $this->cache->get($this->cacheKey);

        if ($cached && isset($cached['token'], $cached['expires_at']) && now()->lt($cached['expires_at'])) {
            return $cached['token'];
        }

        if (!$cached) {
            $token = $this->oldToken;
            return $this->generateToken($token ?? null);
        }

        return $this->generateToken($cached['token'] ?? null);
    }

    public function refreshToken(): string
    {
        if ($this->oldToken) {
            $this->generateToken($this->oldToken);
            $this->logger->info('Token refreshed');
            return "Token refreshed";
        } else {
            $this->logger->error('Old Token Error: Token could not be refreshed');
            return "Old Token Error: Token could not be refreshed";
        }
    }

    protected function generateToken($oldToken = null)
    {
        try {
            $response = $this->client->post('/psms/api/messages/token?action=generate', [
                'headers' => [
                    'apiKey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $oldToken ? ['old_token' => $oldToken] : [],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['token'])) {
                $tokenData = [
                    'token' => $data['token'],
                    'expires_at' => $data['expiryDate'],
                ];

                $this->cache->set($this->cacheKey, $tokenData, $tokenData['expires_at']);
                return $data['token'];
            }

            $this->logger->error('Token generation response missing required keys.');
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Token generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enable, disable, or delete a token.
     */
    public function manageToken($action, $token)
    {
        if (!in_array($action, ['enable', 'disable', 'delete'])) {
            return ['error' => 'Invalid token action.'];
        }

        return $this->client->post("/token?action=$action", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => ['token' => $token]
        ]);
    }
}
