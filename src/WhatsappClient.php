<?php

namespace Renderbit\LaravelWhatsapp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Renderbit\LaravelWhatsapp\Constants\ErrorCodes;

class WhatsappClient
{
    protected Client $client;
    protected TokenManager $tokenManager;
    protected string $apiUrl;
    protected string $apiKey;
    protected string $username;
    protected string $businessVMN;
    protected LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger, CacheInterface $cache)
    {
        $this->apiUrl = rtrim($config['api_base_url'], '/'); // Remove trailing slash
        $this->apiKey = $config['api_key'];
        $this->businessVMN = $config['whatsapp_business_number'];
        $this->username = $config['whatsapp_username'];
        $this->logger = $logger;

        $this->tokenManager = new TokenManager($config, $logger, $cache);

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 15,  // Connection timeout
            'connect_timeout' => 15,  // Connection timeout
            'verify' => false,  // Ensure SSL verification
        ]);

    }

    protected function getErrorMessage(int $code): string
    {
        return ErrorCodes::MAP[$code] ?? 'An unknown error occurred';
    }

    /**
     * Send a WhatsApp message.
     */
    public function sendMessage(string $to, string $templateId, array $additionalParameters = []): array
    {
        try {
            $token = $this->tokenManager->getToken();

            if (!$token) {
                return ['success' => false, 'message' => 'Authentication token unavailable.'];
            }

            $templateInfo = $templateId;

            if (!empty($additionalParameters)) {
                $templateInfo .= '~' . implode('~', $additionalParameters);
            }

            $messageBody = [
                "@VER" => "1.2",
                "USER" => [
                    "@USERNAME" => $this->username,
                    "@PASSWORD" => $token,
                    "@CH_TYPE" => "4",
                    "@UNIXTIMESTAMP" => ""
                ],
                "DLR" => [
                    "@URL" => ""
                ],
                "SMS" => [[
                    "@UDH" => "0",
                    "@CODING" => "1",
                    "@TEXT" => "",
                    "@TEMPLATEINFO" => $templateInfo,
                    "@CONTENTTYPE" => "",
                    "@TYPE" => "",
                    "@MSGTYPE" => "1",
                    "@MEDIADATA" => "",
                    "@B_URLINFO" => "",
                    "@PROPERTY" => "0",
                    "@ID" => "",
                    "ADDRESS" => [[
                        "@FROM" => $this->businessVMN,
                        "@TO" => $to,
                        "@SEQ" => "1",
                        "@TAG" => ""
                    ]]
                ]]
            ];

            $response = $this->sendRequest('POST', '/psms/servlet/psms.JsonEservice', [
                'Authorization' => 'Bearer ' . $token
            ], $messageBody);

            $this->logger->info('WhatsApp API Response: ', $response);

            // Check for API response structure
            $ack = $response['MESSAGEACK']['GUID'] ?? null;
            if (!$ack) {
                return ['success' => false, 'message' => 'Invalid API response format.'];
            }

            // Handle errors from response
            if (isset($ack['ERROR']['CODE'])) {
                $errorCode = $ack['ERROR']['CODE'];
                $message = $this->getErrorMessage($errorCode);
                return ['success' => false, 'message' => $message];
            }

            return ['success' => true, 'message' => 'Message delivered successfully.'];
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    /**
     * Generic function to handle API requests.
     */
    protected function sendRequest($method, $endpoint, $headers = [], $body = [])
    {
        try {
            // Create request options
            $options = [
                'headers' => array_merge(['Content-Type' => 'application/json', 'Accept' => '*/*', 'User-Agent' => 'insomnia/11.0.1'], $headers),
                'body' => json_encode($body),
            ];

            // Send the request
            $response = $this->client->request($method, $endpoint, [
                ...$options,
//                'debug' => fopen(storage_path('logs/guzzle.log'), 'w')
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Capture and log request details from the exception if available

            $this->logger->error('Request Exception: ' . $e->getMessage());

            if ($e->hasResponse()) {

                $statusCode = $e->getResponse()->getStatusCode();

                if ($statusCode == 401) {
                    $this->tokenManager->refreshToken();
                }

                $this->logger->error("API Request failed", [
                    'status_code' => $statusCode,
                    'error_body' => (string)$e->getResponse()->getBody(),
                ]);

            } else {
                $this->logger->error("API Request failed: " . $e->getMessage());
            }

            return ['error' => 'API request failed. Check logs for details.'];
        }
    }
}

