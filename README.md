# Laravel WhatsApp (renderbit/laravel-whatsapp)

[![Tests](https://github.com/RenderbitTechnologies/laravel-whatsapp/actions/workflows/tests.yml/badge.svg)](https://github.com/RenderbitTechnologies/laravel-whatsapp/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011-FF2D20?logo=laravel)

A **framework-agnostic**, Laravel-ready PHP package for sending template-based WhatsApp messages via the Renderbit WhatsApp API.

---

## 🚀 Features

- ✅ **Works with Laravel 10/11, Symfony, Slim, or any PHP 8.1+ app**
- 🔐 **PSR-16 token caching** — automatic token generation, caching, and refresh with configurable TTL
- 📄 **Template-based messaging** — pass template IDs with dynamic parameters
- 🧠 **51 built-in error codes** — maps API error codes to human-readable messages
- 🧰 **PSR-compliant** — PSR-3 logging, PSR-16 caching, PSR-4 autoloading
- 📡 **DLR endpoint** — built-in delivery report webhook route
- 🧪 **100% test coverage** — PHPUnit test suite with CI on 6 matrix configurations
- 🔌 **Laravel auto-discovery** — service provider auto-registers, facade ready to use

---

## 📦 Installation

```bash
composer require renderbit/laravel-whatsapp
```

### Requirements

| Dependency | Version |
|---|---|
| PHP | >= 8.1 |
| Laravel (optional) | 10.x \| 11.x |
| guzzlehttp/guzzle | ^7.9 |
| illuminate/support | ^10.0 \| ^11.0 |
| psr/log | ^1.1 \| ^2.0 \| ^3.0 |
| psr/simple-cache | ^1.0 \| ^2.0 \| ^3.0 |

---

## ⚙️ Laravel Setup

> **Note:** This package uses [Laravel auto-discovery](https://laravel.com/docs/packages#package-discovery), so the service provider is registered automatically. No manual registration needed.

### 1. Publish Config (optional)

```bash
php artisan vendor:publish --tag=whatsapp-config
```

This publishes the config file and routes to your application:
- `config/whatsapp.php` — configuration values
- `routes/vendor/whatsapp-api.php` — DLR webhook route

### 2. Environment Configuration

Add these to your `.env`:

```env
WHATSAPP_API_BASE_URL=https://your-api-base-url.com
WHATSAPP_API_KEY=your-api-key
WHATSAPP_BUSINESS_NUMBER=918888888888
WHATSAPP_USERNAME=your-username
WHATSAPP_OLD_TOKEN=previous-token-if-refreshing
```

### Configuration Reference (`config/whatsapp.php`)

| Key | Env Variable | Description |
|---|---|---|
| `api_base_url` | `WHATSAPP_API_BASE_URL` | Base URL for the Renderbit WhatsApp API |
| `api_key` | `WHATSAPP_API_KEY` | API key for authentication |
| `whatsapp_business_number` | `WHATSAPP_BUSINESS_NUMBER` | Business phone number (sender) |
| `whatsapp_username` | `WHATSAPP_USERNAME` | Username for the API |
| `old_token` | `WHATSAPP_OLD_TOKEN` | Previous token (used during token refresh) |

---

## 🧱 Usage

### In Laravel

#### Via Dependency Injection

```php
use Renderbit\LaravelWhatsapp\WhatsappClient;

class MessageController extends Controller
{
    public function send(WhatsappClient $whatsapp)
    {
        $response = $whatsapp->sendMessage(
            '919876543210',        // Phone number (no special chars)
            '1043144443',          // Template ID
            ['John Doe', '1500']   // Template parameters
        );

        if ($response['success']) {
            return back()->with('success', 'Message sent!');
        }

        return back()->with('error', $response['message']);
    }
}
```

#### Via Facade

```php
use Renderbit\LaravelWhatsapp\Facades\Whatsapp;

$response = Whatsapp::sendMessage('919876543210', '1043144443', ['Jane', '2500']);
```

#### Via `app()` Helper

```php
$response = app('whatsapp')->sendMessage('919876543210', '1043144443', ['Jane', '2500']);
```

#### Response Format

All `sendMessage()` calls return a uniform response array:

```php
// Success
['success' => true,  'message' => 'Message delivered successfully.']

// Failure (authentication)
['success' => false, 'message' => 'Authentication token unavailable.']

// Failure (API error code)
['success' => false, 'message' => 'Invalid phone number'] // ErrorCodes::MAP[10001]

// Failure (HTTP/network error)
['success' => false, 'message' => 'API request failed. Check logs for details.']
```

### In Standalone PHP

```php
use Renderbit\LaravelWhatsapp\WhatsappClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$logger = new Logger('whatsapp');
$logger->pushHandler(new StreamHandler('php://stdout'));
$cache = new FilesystemAdapter('whatsapp', 0, '/tmp/cache');

$client = new WhatsappClient([
    'api_base_url' => 'https://your-api-base-url.com',
    'api_key' => 'your-api-key',
    'whatsapp_business_number' => '918888888888',
    'whatsapp_username' => 'your-username',
    'old_token' => null,
], $logger, $cache);

$response = $client->sendMessage('919876543210', '1043144443', ['Alice', '3000']);
```

---

## 🔐 Token Management

The `TokenManager` class handles all authentication token lifecycle automatically.

| Method | Description |
|---|---|
| `getToken()` | Returns a valid token from cache or generates a new one |
| `refreshToken()` | Forces a token refresh using the old token |
| `manageToken('enable' \| 'disable' \| 'delete', $token)` | Manages token state |

**How it works:**

1. `getToken()` checks PSR-16 cache for a valid (non-expired) token
2. If cached and valid, returns immediately
3. If expired or missing, calls `generateToken()` which requests a new token from the API
4. The new token and its expiry are stored in cache with the correct TTL (in seconds)
5. On HTTP 401 responses, `WhatsappClient::sendRequest()` automatically triggers `refreshToken()`

You can swap the cache backend to Redis, APCu, database, or any PSR-16 implementation.

---

## 📡 DLR Webhook

The package registers a POST route for delivery reports:

```
POST /whatsapp/dlr
```

The `WhatsAppDLRController::receiveDLR()` handler logs incoming delivery reports via Laravel's `Log::info()` and responds with `{"status": "success"}`.

To customize, extend the controller or disable the routes and define your own:

```php
// Don't publish routes — override in your app's RouteServiceProvider
```

---

## 🗺 Error Code Reference

The package ships with **51 mapped error codes** in `Renderbit\LaravelWhatsapp\Constants\ErrorCodes`:

| Code | Message |
|---|---|
| 28694 | Invalid template parameters |
| 10001 | Invalid phone number |
| 52992 | Username / Password incorrect |
| 52995 | Daily Credit limit Reached |
| 57089 | Contract expired |
| 57090 | User credit expired |
| 57091 | User disabled |
| 65280 | Service is temporarily unavailable |
| 65535 | Message does not conform to DTD |
| 28673–28704 | Validation errors (destination, sender, template, etc.) |
| 2009–2026 | Template format errors |
| 9988 | Unknown failure |
| 38679–65536 | Campaign and system errors |

You can customize messages by extending or modifying the `ErrorCodes::MAP` array.

---

## 🧪 Testing

```bash
composer install
vendor/bin/phpunit
```

The test suite covers:
- **WhatsappClient** — message sending, error handling, HTTP failures, token exhaustion
- **TokenManager** — cache hit/miss, token generation, refresh, invalid actions
- **WhatsAppDLRController** — DLR endpoint acceptance
- **WhatsappServiceProvider** — config merge, singleton binding, facade alias, publishable tags
- **Whatsapp Facade** — accessor resolution
- **ErrorCodes** — all 51 error codes validated

### CI Matrix

The package is tested via GitHub Actions across 6 configurations:

| PHP | Laravel |
|---|---|
| 8.1 | 10 |
| 8.2 | 10 |
| 8.2 | 11 |
| 8.3 | 10 |
| 8.3 | 11 |
| 8.4 | 11 |

---

## 📁 Project Structure

```
renderbit/laravel-whatsapp
├── config/
│   └── whatsapp.php             # Package configuration
├── routes/
│   └── api.php                  # DLR webhook route
├── src/
│   ├── Constants/
│   │   └── ErrorCodes.php       # 51 API error code mappings
│   ├── Facades/
│   │   └── Whatsapp.php         # Laravel facade
│   ├── Http/
│   │   └── Controllers/
│   │       └── WhatsAppDLRController.php  # DLR webhook handler
│   ├── TokenManager.php         # Token lifecycle (cache/generate/refresh)
│   ├── WhatsappClient.php       # Main API client
│   └── WhatsappServiceProvider.php  # Laravel service provider
├── tests/
│   ├── TestCase.php             # Base test case (Mockery)
│   ├── LaravelTestCase.php      # Base test case (Orchestra Testbench)
│   ├── ErrorCodesTest.php
│   ├── TokenManagerTest.php
│   ├── WhatsappClientTest.php
│   ├── WhatsAppDLRControllerTest.php
│   ├── WhatsappFacadeTest.php
│   └── WhatsappServiceProviderTest.php
├── .github/workflows/
│   └── tests.yml                # CI/CD workflow
├── composer.json
├── phpunit.xml.dist
└── README.md
```

---

## 🧩 Extending & Customization

- **Error messages** — Modify `src/Constants/ErrorCodes.php` to localize or customize API error messages
- **Cache backend** — The PSR-16 `CacheInterface` can be swapped for Redis, APCu, Memcached, or any compliant adapter
- **Logger** — The PSR-3 `LoggerInterface` supports Monolog, Loggly, Laravel's Log facade, etc.
- **DLR handling** — Extend `WhatsAppDLRController` to implement custom delivery report logic (store in DB, forward to webhook, etc.)
- **HTTP client** — `GuzzleHttp\Client` is used internally; customize timeouts/headers in `WhatsappClient::__construct()`

---

## 🤝 Contributing

1. Fork the repository
2. Install dependencies: `composer install`
3. Write/run tests: `vendor/bin/phpunit`
4. Submit a pull request

Please ensure all tests pass and maintain at least the current coverage level.

---

## 📄 License

MIT © [Renderbit Technologies](https://github.com/RenderbitTechnologies/laravel-whatsapp)
