# EduDex API Client - Standalone PHP Usage

This guide explains how to use the EduDex API client in non-SilverStripe PHP projects.

## Installation

The client has minimal dependencies and works with any PHP 8.1+ project.

### Requirements

- PHP 8.1 or higher
- GuzzleHTTP ^7.0 (install via Composer)
- PSR-3 LoggerInterface (optional, for logging)

### Manual Installation

Copy the `app/src/EduDex/` directory to your project (excluding the `Integration/` folder if you don't need SilverStripe support).

### Composer Autoloading

Add to your `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Restruct\\EduDex\\": "src/EduDex/"
        }
    },
    "require": {
        "guzzlehttp/guzzle": "^7.0",
        "psr/log": "^3.0"
    }
}
```

Then run:
```bash
composer dump-autoload
```

## Basic Usage

### Simple Initialization

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;

// Using environment variable EDUDEX_API_TOKEN
$client = new Client();

// Or pass token explicitly
$client = new Client('your-bearer-token-here');

// With custom base URL
$client = new Client('your-token', 'https://api.edudex.nl/data/v1/');
```

### Configuration Array

```php
use Restruct\EduDex\Client;

$config = [
    'bearer_token' => 'your-token-here',
    'api_base_url' => 'https://api.edudex.nl/data/v1/',
    'timeout' => 30,
];

$client = Client::fromConfig($config);
```

### With Logger

```php
use Restruct\EduDex\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('edudex');
$logger->pushHandler(new StreamHandler('path/to/edudex.log', Logger::DEBUG));

$client = new Client('your-token', null, [], $logger);
```

## Environment Variables

The client automatically reads from `EDUDEX_API_TOKEN` environment variable:

```bash
# In .env or shell
export EDUDEX_API_TOKEN="your-jwt-bearer-token"
```

```php
// No token parameter needed
$client = new Client();
```

## Complete Examples

### Listing Organizations

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\EduDexException;

try {
    $client = new Client();
    $organizations = $client->organizations()->list();

    foreach ($organizations as $org) {
        echo "Organization: " . $org->getLocalizedName('nl') . "\n";
        echo "  ID: {$org->id}\n";
        echo "  Roles: " . implode(', ', $org->roles) . "\n";
        echo "  VAT Exempt: " . ($org->vatExempt ? 'Yes' : 'No') . "\n";
        echo "\n";
    }
} catch (EduDexException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Working with Programs

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;

$client = new Client();

// List programs for a supplier
$programs = $client->suppliers()->listPrograms('supplier-id');

foreach ($programs as $programInfo) {
    echo "Program: {$programInfo['programId']}\n";
}

// Get specific program
$program = $client->suppliers()->getProgram('supplier-id', 'program-id', 'client-id');

echo "Title (NL): " . $program->getTitle('nl') . "\n";
echo "Title (EN): " . $program->getTitle('en') . "\n";
echo "Description: " . $program->getDescription('nl') . "\n";
```

### Validating Program Data

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;

$client = new Client();

$programData = [
    'editor' => 'Admin User',
    'format' => 'application/vnd.edudex.program+json',
    'generator' => 'My CMS v1.0',
    'lastEdited' => date('c'),
    'programDescriptions' => [
        'title' => [
            'nl' => 'Cursus Titel',
            'en' => 'Course Title',
        ],
    ],
    // ... rest of program data
];

$result = $client->validations()->validateProgram($programData);

if ($result->isValid()) {
    echo "✓ Validation passed!\n";

    if ($result->hasWarnings()) {
        echo "\nWarnings:\n";
        foreach ($result->getWarnings() as $warning) {
            echo "  ⚠ {$warning->message}\n";
        }
    }

    // Safe to submit
    $program = $client->suppliers()->upsertProgram(
        'supplier-id',
        'program-id',
        'client-id',
        $programData
    );

    echo "\n✓ Program saved successfully!\n";
} else {
    echo "✗ Validation failed:\n\n";

    foreach ($result->getErrors() as $error) {
        echo "  • {$error->message}";
        if ($error->contextPath) {
            echo " (at {$error->contextPath})";
        }
        echo "\n";
    }
}
```

### Bulk Program Retrieval

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;

$client = new Client();

$programsToFetch = [
    ['orgUnitId' => 'supplier-1', 'programId' => 'prog-1', 'clientId' => 'public'],
    ['orgUnitId' => 'supplier-2', 'programId' => 'prog-2', 'clientId' => 'client-1'],
    ['orgUnitId' => 'supplier-3', 'programId' => 'prog-3', 'clientId' => 'public'],
];

$response = $client->programs()->bulk($programsToFetch);

// Process successful results
$successful = $client->programs()->getSuccessful($response);
echo "Retrieved " . count($successful) . " programs:\n";

foreach ($successful as $program) {
    echo "  - " . $program->getTitle('nl') . "\n";
}

// Handle failures
$failed = $client->programs()->getFailed($response);
if (!empty($failed)) {
    echo "\nFailed to retrieve " . count($failed) . " programs:\n";
    foreach ($failed as $failure) {
        echo "  - {$failure['programId']}: {$failure['error']}\n";
    }
}
```

### Managing Webhooks

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;

$client = new Client();
$orgId = 'your-org-id';

// Create webhook
$webhook = $client->organizations()->createWebhook(
    $orgId,
    'https://your-site.com/webhooks/edudex',
    ['catalog', 'program']
);

echo "Webhook created: {$webhook->id}\n";
echo "URL: {$webhook->url}\n";
echo "Active: " . ($webhook->active ? 'Yes' : 'No') . "\n";

// List webhooks
$webhooks = $client->organizations()->listWebhooks($orgId);

foreach ($webhooks as $wh) {
    echo "\nWebhook: {$wh->id}\n";
    echo "  URL: {$wh->url}\n";
    echo "  Events: " . implode(', ', $wh->events) . "\n";

    if ($wh->hasBeenCalled()) {
        if ($wh->wasLastCallSuccessful()) {
            echo "  Status: ✓ OK (last: {$wh->lastCalled->format('Y-m-d H:i:s')})\n";
        } else {
            echo "  Status: ✗ FAILING\n";
            if ($error = $wh->getLastError()) {
                echo "  Error: {$error}\n";
            }
        }
    } else {
        echo "  Status: Never called\n";
    }
}

// Test webhook
$testResult = $client->organizations()->testWebhook($orgId, $webhook->id);
echo "\nWebhook test result:\n";
print_r($testResult);
```

## Error Handling

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Exceptions\ValidationException;
use Restruct\EduDex\Exceptions\ApiException;

try {
    $client = new Client();
    $org = $client->organizations()->get('org-id');

} catch (AuthenticationException $e) {
    // 401/403 errors
    echo "Authentication error: {$e->getMessage()}\n";
    echo "Status code: {$e->getCode()}\n";

} catch (ValidationException $e) {
    // 400 validation errors
    echo "Validation error: {$e->getMessage()}\n";

    if ($e->hasErrors()) {
        echo "\nErrors:\n";
        foreach ($e->getErrorMessages() as $msg) {
            echo "  • $msg\n";
        }
    }

    if ($e->hasWarnings()) {
        echo "\nWarnings:\n";
        foreach ($e->getWarningMessages() as $msg) {
            echo "  ⚠ $msg\n";
        }
    }

} catch (ApiException $e) {
    // Other HTTP errors
    if ($e->isClientError()) {
        echo "Client error (4xx): {$e->getMessage()}\n";
    } elseif ($e->isServerError()) {
        echo "Server error (5xx): {$e->getMessage()}\n";
    }

    // Get additional context
    if ($e->hasContextKey('response')) {
        print_r($e->getContextValue('response'));
    }

} catch (EduDexException $e) {
    // Catch-all for any EduDex error
    echo "API error: {$e->getMessage()}\n";
    echo "Code: {$e->getCode()}\n";
}
```

## Configuration Options

The `Client` constructor and `fromConfig()` accept these options:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `bearer_token` | string | `EDUDEX_API_TOKEN` env | JWT bearer token |
| `api_base_url` | string | `https://api.edudex.nl/data/v1/` | API base URL |
| `timeout` | int | 30 | Request timeout (seconds) |
| `debug` | bool | false | Enable debug mode |
| `cache_ttl` | int | 3600 | Cache TTL (seconds) |

Example:

```php
$config = [
    'bearer_token' => 'your-token',
    'api_base_url' => 'https://api.edudex.nl/data/v1/',
    'timeout' => 60,
    'debug' => true,
    'cache_ttl' => 7200,
];

$client = Client::fromConfig($config, $logger);
```

## Comparison with SilverStripe Usage

| Feature | Standalone PHP | SilverStripe |
|---------|---------------|--------------|
| Base Class | `Restruct\EduDex\Client` | `Restruct\EduDex\Integration\SilverStripe\SilverStripeClient` |
| Config | Array or constructor params | YML + Config API |
| Dependency Injection | Manual instantiation | Injector support |
| Logging | Pass PSR-3 logger | SilverStripe Logger |
| CMS Integration | N/A | SiteConfig extension |

### Standalone

```php
$client = new Client('token');
$orgs = $client->organizations()->list();
```

### SilverStripe

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

$client = SilverStripeClient::singleton();
$orgs = $client->organizations()->list();
```

## Best Practices

1. **Always use environment variables** for tokens in production
2. **Implement error handling** for all API calls
3. **Use validation endpoints** before submitting data
4. **Log API interactions** in production for debugging
5. **Cache responses** where appropriate to reduce API calls
6. **Monitor webhook health** if using webhook subscriptions

## Troubleshooting

### "Missing token" error

Ensure `EDUDEX_API_TOKEN` is set or pass token explicitly:

```php
$client = new Client('your-token-here');
```

### SSL/TLS errors

If you encounter SSL certificate issues:

```php
$config = ['verify' => false]; // Not recommended for production!
$client = new Client('token', null, $config);
```

### Timeout errors

Increase timeout for slow connections:

```php
$config = ['timeout' => 60];
$client = new Client('token', null, $config);
```

## Complete Application Example

See `examples/standalone-app.php` for a complete working application.

## Support

- **API Documentation**: https://api.edudex.nl/data/v1/
- **Full Documentation**: See `README.md` for complete API reference
- **SilverStripe Integration**: See main `README.md` for SilverStripe usage
