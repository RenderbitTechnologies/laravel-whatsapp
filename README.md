# Laravel WhatsApp (renderbit/laravel-whatsapp)

A **framework-agnostic**, Laravel-ready PHP package for sending WhatsApp messages via Renderbit's official WhatsApp API.

---

## 🚀 Features

- ✅ Works with Laravel, Symfony, Slim, or any PHP app
- 🔐 Token caching and refresh support
- 📄 Template-based WhatsApp message support
- 🧠 Built-in error mapping from API error codes to human-readable messages
- 🧰 Minimal dependencies (PSR-compliant logging and caching)

---

## 📦 Installation

### Laravel (via Git or Packagist)

```bash
composer require renderbit/laravel-whatsapp
```

If you're using it via a private Git repo:

```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/renderbit/laravel-whatsapp"
  }
]
```

---

## ⚙️ Laravel Setup

### Publish the config (optional):
```bash
php artisan vendor:publish --tag=whatsapp-config
```

### .env Configuration
```
WHATSAPP_API_BASE_URL=your-base-url-here
WHATSAPP_API_KEY=your-api-key
WHATSAPP_USERNAME=your-username
WHATSAPP_BUSINESS_NUMBER=918888888888
WHATSAPP_OLD_TOKEN=null
```

---

## 🧱 Basic Usage

### In Laravel:
```php
use Renderbit\LaravelWhatsapp\WhatsappClient;

$response = app(WhatsappClient::class)->sendMessage(
    '<phone-number-here-with-country-code>', // 91988776655: No special characters allowed
    '<vf-template-id>', // Eg. 1043144443
    ['John Doe', '1500']
);

if ($response['success']) {
    echo "✅ Message sent!";
} else {
    echo "❌ Failed: " . $response['message'];
}
```

---

### In Standalone PHP

```php
use Renderbit\LaravelWhatsapp\WhatsappClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$logger = new Logger('whatsapp');
$logger->pushHandler(new StreamHandler('php://stdout'));
$cache = new FilesystemAdapter();

$config = [
    'api_base_url' => 'your-base-url-here',
    'api_key' => 'your-api-key',
    'whatsapp_business_number' => '918888888888',
    'whatsapp_username' => 'your-username',
    'old_token' => null
];

$client = new WhatsappClient($config, $logger, $cache);
$response = $client->sendMessage('<phone-number-here>', '<vf-template-id>', ['John', '1500']);

if ($response['success']) {
    echo "Message sent!";
} else {
    echo "Error: " . $response['message'];
}
```

---

## 🧩 Extending & Customization

- Error code mappings are stored in `Constants\ErrorCodes.php`
- You can update this map to localize or customize messages
- The token management logic is PSR-compliant and can be swapped for Redis, APCu, etc.

## 📄 License

MIT © Renderbit Technologies
