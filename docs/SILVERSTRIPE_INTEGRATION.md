# SilverStripe Integration Guide

This guide explains how to use the EduDex API Client with SilverStripe projects.

## Installation

```bash
composer require restruct/edudex-api-client
```

The SilverStripe integration layer is included in the package under `Restruct\EduDex\Integration\SilverStripe\`.

## Configuration

### 1. Environment Variable

Add your bearer token to `.env`:

```bash
EDUDEX_API_TOKEN="your-jwt-bearer-token-here"
```

### 2. YML Configuration

Create or edit `app/_config/edudex.yml`:

```yaml
---
Name: app-edudex
---

# SilverStripe Client configuration
# Note: Bearer token cannot be set via Config for security.
# Set via EDUDEX_API_TOKEN environment variable or pass to constructor.
Restruct\EduDex\Integration\SilverStripe\SilverStripeClient:
  # API base URL (default production URL)
  api_base_url: 'https://api.edudex.nl/data/v1/'

  # Request timeout in seconds
  timeout: 30

  # Enable debug logging (logs all requests/responses)
  debug: false

  # Cache TTL for API responses (seconds, 0 to disable)
  cache_ttl: 3600

# Configure Injector to use SilverStripeClient
SilverStripe\Core\Injector\Injector:
  Restruct\EduDex\Client:
    class: Restruct\EduDex\Integration\SilverStripe\SilverStripeClient

# Add EduDex configuration to SiteConfig (optional CMS integration)
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\EduDex\Integration\SilverStripe\Extensions\SiteConfigExtension
```

### 3. Build Database

Run dev/build to apply configuration:

```bash
vendor/bin/sake dev/build flush=1
```

## Usage

### Using Singleton

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

$client = SilverStripeClient::singleton();
$organizations = $client->organizations()->list();
```

### Using Dependency Injection

```php
use Restruct\EduDex\Client;
use SilverStripe\Core\Injector\Injector;

// Automatically gets SilverStripeClient via Injector configuration
$client = Injector::inst()->get(Client::class);
$organizations = $client->organizations()->list();
```

### In a Controller

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use SilverStripe\CMS\Controllers\ContentController;

class CoursePageController extends ContentController
{
    public function Suppliers()
    {
        $client = SilverStripeClient::singleton();

        try {
            return $client->suppliers()->list();
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch suppliers: ' . $e->getMessage());
            return [];
        }
    }
}
```

### In a Task

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use SilverStripe\Dev\BuildTask;

class SyncEduDexDataTask extends BuildTask
{
    private static $segment = 'sync-edudex';

    protected $title = 'Sync EduDex Data';

    protected $description = 'Synchronize course data from EDU-DEX API';

    public function run($request)
    {
        $client = SilverStripeClient::singleton();

        try {
            $suppliers = $client->suppliers()->list();

            foreach ($suppliers as $supplier) {
                echo "Processing: {$supplier->getLocalizedName('nl')}\n";

                $programs = $client->suppliers()->listPrograms($supplier->id);
                echo "  Found " . count($programs) . " programs\n";

                // Process programs...
            }

            echo "\n✓ Sync completed successfully\n";
        } catch (\Exception $e) {
            echo "✗ Error: {$e->getMessage()}\n";
        }
    }
}
```

### In a QueuedJob

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

class SyncCoursesJob extends AbstractQueuedJob
{
    public function getTitle()
    {
        return 'Sync EduDex Courses';
    }

    public function process()
    {
        $client = SilverStripeClient::singleton();

        try {
            $programs = $client->programs()->bulk([
                // Program identifiers...
            ]);

            $successful = $client->programs()->getSuccessful($programs);

            $this->currentStep++;
            $this->totalSteps = count($successful);

            foreach ($successful as $program) {
                // Process each program...
                $this->currentStep++;
            }

            $this->isComplete = true;
        } catch (\Exception $e) {
            $this->addMessage("Error: {$e->getMessage()}");
            $this->isComplete = true;
        }
    }
}
```

## CMS Integration

The `SiteConfigExtension` adds an "EduDex" tab to Settings in the CMS.

### Features

- Configure API bearer token directly in CMS
- Set custom API base URL
- Test connection with real-time status
- Visual feedback for connection issues

### Accessing Configuration

```php
use SilverStripe\SiteConfig\SiteConfig;

$config = SiteConfig::current_site_config();

// Get configured token
$token = $config->getEduDexToken();

// Get configured base URL
$baseUrl = $config->getEduDexBaseUrl();

// Get configured client instance
$client = $config->getEduDexClient();

if ($client) {
    $organizations = $client->organizations()->list();
}
```

## Configuration Options

### SilverStripeClient Config

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `api_base_url` | string | `https://api.edudex.nl/data/v1/` | API base URL |
| `timeout` | int | 30 | Request timeout (seconds) |
| `debug` | bool | false | Enable debug logging |
| `cache_ttl` | int | 3600 | Cache TTL (seconds) |

**Note:** The bearer token **cannot** be set via Config API for security reasons. It must be provided via:
- `EDUDEX_API_TOKEN` environment variable (recommended), or
- Constructor parameter when manually instantiating the client

### Accessing Config Values

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

$client = SilverStripeClient::singleton();

// Check if debug mode is enabled
if ($client->isDebugEnabled()) {
    // Debug logging active
}

// Get cache TTL
$ttl = $client->getCacheTTL();
```

## Logging

The SilverStripeClient integrates with SilverStripe's logging system:

```php
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

$logger = Injector::inst()->get(LoggerInterface::class);
$client = new SilverStripeClient(null, null, [], $logger);

// All API calls will be logged
$organizations = $client->organizations()->list();
```

Configure logging in `app/_config/logging.yml`:

```yaml
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface.edudex:
    calls:
      - [pushHandler, ['%$EduDexLogHandler']]
  EduDexLogHandler:
    class: Monolog\Handler\StreamHandler
    constructor:
      - '../edudex.log'
      - 'debug'
```

## Caching

Use SilverStripe's cache API to cache API responses:

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use SilverStripe\Core\Cache\CacheFactory;

class CachedEduDexService
{
    public function getOrganizations()
    {
        $cache = CacheFactory::inst()->create('EduDexCache');
        $cacheKey = 'organizations_list';

        if ($cached = $cache->get($cacheKey)) {
            return unserialize($cached);
        }

        $client = SilverStripeClient::singleton();
        $organizations = $client->organizations()->list();

        $cache->set($cacheKey, serialize($organizations), 3600);

        return $organizations;
    }
}
```

Configure cache in `app/_config/cache.yml`:

```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Core\Cache\CacheFactory:
    constructor:
      - EduDexCache
      - 'apcu'
```

## Error Handling

```php
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Exceptions\ValidationException;
use Restruct\EduDex\Exceptions\ApiException;
use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

$client = SilverStripeClient::singleton();

try {
    $org = $client->organizations()->get('org-id');

} catch (AuthenticationException $e) {
    // Log authentication failures
    SS_Log::log("EduDex auth failed: {$e->getMessage()}", SS_Log::ERR);

    // Notify admin
    Email::create()
        ->setTo('admin@example.com')
        ->setSubject('EduDex API Authentication Failed')
        ->setBody($e->getMessage())
        ->send();

} catch (ValidationException $e) {
    // Handle validation errors
    foreach ($e->getErrors() as $error) {
        SS_Log::log("Validation error: {$error['message']}", SS_Log::WARN);
    }

} catch (ApiException $e) {
    // Handle API errors
    if ($e->isServerError()) {
        // Retry logic for server errors
        $this->queueRetry();
    }

} catch (EduDexException $e) {
    // Fallback error handling
    SS_Log::log("EduDex error: {$e->getMessage()}", SS_Log::ERR);
}
```

## Testing

### Unit Testing

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use SilverStripe\Dev\SapphireTest;

class EduDexClientTest extends SapphireTest
{
    public function testClientCanBeInstantiated()
    {
        $client = SilverStripeClient::singleton();
        $this->assertInstanceOf(SilverStripeClient::class, $client);
    }

    public function testConfigurationIsLoaded()
    {
        $client = SilverStripeClient::singleton();
        $config = $client->getConfig('api_base_url');

        $this->assertEquals('https://api.edudex.nl/data/v1/', $config);
    }
}
```

### Functional Testing

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use SilverStripe\Dev\FunctionalTest;

class CoursePageTest extends FunctionalTest
{
    protected static $fixture_file = 'CoursePageTest.yml';

    public function testSuppliersAreDisplayed()
    {
        $client = $this->createMock(SilverStripeClient::class);
        $client->method('suppliers')
            ->willReturn($this->getMockSuppliers());

        // Test implementation...
    }
}
```

## Migration from App\EduDex

If you're migrating from the old `App\EduDex` namespace:

1. Update YML configuration class names to `Restruct\EduDex\Integration\SilverStripe\...`
2. Run `dev/build flush=1`
3. Update any explicit class references in your code
4. The Injector mapping ensures backward compatibility for most use cases

## Troubleshooting

### "Missing token" error

Ensure `EDUDEX_API_TOKEN` is set in `.env` or configured in YML/SiteConfig.

### "Class not found" error

Run `composer dump-autoload` and `dev/build flush=1`.

### Configuration not loading

Check YML file syntax and ensure file is in `app/_config/` directory.

### CMS integration not showing

Verify the extension is applied and run `dev/build flush=1`.

## Support

- **Package Issues**: https://github.com/restruct/edudex-api-client/issues
- **SilverStripe Forums**: https://forum.silverstripe.org/
- **API Documentation**: https://api.edudex.nl/data/v1/
