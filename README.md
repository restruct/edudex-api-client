# EduDex API Client

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://php.net)

PHP client library for the [EDU-DEX Data API](https://api.edudex.nl/data/v1/). Framework-agnostic with optional SilverStripe integration.

## Features

- 🎯 **Complete API Coverage** - All endpoints from the EDU-DEX OpenAPI specification
- 🔌 **Framework Agnostic** - Works with any PHP 8.1+ project
- 🔧 **Optional SilverStripe Integration** - Full Config API, Extensions, and Injector support
- 🎨 **Clean Architecture** - PSR-4 autoloading, typed models, endpoint classes
- 🛡️ **Type Safe** - PHP 8.1+ with strict type hints and return types
- 📦 **Smart Models** - Automatic hydration, serialization, and type casting
- 🌍 **Localization** - Built-in `LocalizedString` helper for multi-language content
- ✅ **Validation** - Pre-submission validation for programs, metadata, and discounts
- 🔐 **Secure** - Bearer token authentication with environment variable support
- 📝 **Well Documented** - Comprehensive PHPDoc blocks and usage examples

## Installation

Install via Composer:

```bash
composer require restruct/edudex-api-client
```

## Quick Start

### Basic Usage (Any PHP Project)

```php
<?php
require 'vendor/autoload.php';

use Restruct\EduDex\Client;

// Initialize with bearer token
$client = new Client('your-jwt-bearer-token');

// Or use environment variable EDUDEX_API_TOKEN
$client = new Client();

// Fetch organizations
$organizations = $client->organizations()->list();

foreach ($organizations as $org) {
    echo $org->getLocalizedName('nl') . "\n";
}
```

### Configuration

```php
use Restruct\EduDex\Client;

// From array
$config = [
    'bearer_token' => 'your-token',
    'api_base_url' => 'https://api.edudex.nl/data/v1/',
    'timeout' => 30,
];
$client = Client::fromConfig($config);

// From environment variable
// Set EDUDEX_API_TOKEN in your .env file
$client = new Client();
```

## Usage Examples

### Working with Organizations

```php
use Restruct\EduDex\Client;

$client = new Client();

// List all organizations
$organizations = $client->organizations()->list();

// Get specific organization
$org = $client->organizations()->get('organization-id');

echo "Name: " . $org->getLocalizedName('nl') . "\n";
echo "Roles: " . implode(', ', $org->roles) . "\n";
echo "Supplier: " . ($org->isSupplier() ? 'Yes' : 'No') . "\n";

// Working with catalogs
$catalogs = $client->organizations()->listStaticCatalogs('org-id');

foreach ($catalogs as $catalog) {
    echo "{$catalog->title}: {$catalog->countActive}/{$catalog->countTotal} programs\n";
}
```

### Managing Programs

```php
// List programs for a supplier
$programs = $client->suppliers()->listPrograms('supplier-id');

// Get specific program
$program = $client->suppliers()->getProgram('supplier-id', 'program-id', 'client-id');

echo "Title: " . $program->getTitle('nl') . "\n";
echo "Description: " . $program->getDescription('en') . "\n";

// Create or update program
$programData = [
    'editor' => 'Admin User',
    'format' => 'application/vnd.edudex.program+json',
    'generator' => 'My CMS v1.0',
    'lastEdited' => date('c'),
    'programDescriptions' => [
        'title' => ['nl' => 'Cursus Titel', 'en' => 'Course Title'],
    ],
    // ... additional program data
];

$client->suppliers()->upsertProgram('supplier-id', 'program-id', 'client-id', $programData);
```

### Validating Data

```php
// Validate program before submission
$result = $client->validations()->validateProgram($programData);

if ($result->isValid()) {
    echo "✓ Validation passed!\n";

    // Submit the program
    $client->suppliers()->upsertProgram(...);
} else {
    echo "✗ Validation failed:\n";

    foreach ($result->getErrors() as $error) {
        echo "  • {$error->message}";
        if ($error->contextPath) {
            echo " (at {$error->contextPath})";
        }
        echo "\n";
    }
}
```

### Bulk Operations

```php
// Fetch multiple programs at once
$programsToFetch = [
    ['orgUnitId' => 'supplier-1', 'programId' => 'prog-1', 'clientId' => 'public'],
    ['orgUnitId' => 'supplier-2', 'programId' => 'prog-2', 'clientId' => 'client-1'],
];

$response = $client->programs()->bulk($programsToFetch);

// Process results
$successful = $client->programs()->getSuccessful($response);
$failed = $client->programs()->getFailed($response);

echo "Retrieved " . count($successful) . " programs\n";
echo "Failed: " . count($failed) . " programs\n";
```

### Error Handling

```php
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Exceptions\ValidationException;
use Restruct\EduDex\Exceptions\ApiException;
use Restruct\EduDex\Exceptions\EduDexException;

try {
    $org = $client->organizations()->get('org-id');

} catch (AuthenticationException $e) {
    // Handle 401/403 errors
    echo "Authentication error: {$e->getMessage()}\n";

} catch (ValidationException $e) {
    // Handle validation errors with detailed messages
    foreach ($e->getErrors() as $error) {
        echo "Error: {$error['message']}\n";
    }

} catch (ApiException $e) {
    // Handle other API errors
    echo "API error: {$e->getMessage()}\n";

} catch (EduDexException $e) {
    // Catch-all
    echo "Error: {$e->getMessage()}\n";
}
```

## SilverStripe Integration

For SilverStripe projects, use the optional integration layer:

```bash
composer require restruct/edudex-api-client
composer require silverstripe/framework:^5.0
```

### Configuration

Add to `app/_config/edudex.yml`:

```yaml
---
Name: app-edudex
---

# SilverStripe Client configuration
Restruct\EduDex\Integration\SilverStripe\SilverStripeClient:
  api_base_url: 'https://api.edudex.nl/data/v1/'
  bearer_token: '`EDUDEX_API_TOKEN`'
  timeout: 30

# Configure Injector
SilverStripe\Core\Injector\Injector:
  Restruct\EduDex\Client:
    class: Restruct\EduDex\Integration\SilverStripe\SilverStripeClient

# Add CMS integration
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\EduDex\Integration\SilverStripe\Extensions\SiteConfigExtension
```

### Usage in SilverStripe

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

// Using singleton
$client = SilverStripeClient::singleton();

// Or via Injector
$client = Injector::inst()->get(SilverStripeClient::class);

// Same API as standalone
$organizations = $client->organizations()->list();
```

See [docs/SILVERSTRIPE_INTEGRATION.md](docs/SILVERSTRIPE_INTEGRATION.md) for complete documentation.

## API Reference

### Endpoints

- **Organizations** - Manage organizations, catalogs, and webhooks
- **Suppliers** - Supplier management, programs, discounts, metadata
- **Accreditors** - Accreditor management and accreditations
- **Programs** - Bulk program operations
- **Validations** - Validate programs, metadata, and discounts

### Models

- `Organization` - Organization with roles and accreditations
- `Supplier` - Training provider
- `Accreditor` - Accreditation organization
- `Accreditation` - Supplier accreditation with validity
- `StaticCatalog` - Manual program catalog
- `DynamicCatalog` - Automatic catalog with filters
- `Webhook` - Event notification endpoint
- `Program` - Training program/course
- `ValidationResult` - Validation response

### Type Helpers

- `LocalizedString` - Multi-language content handler
- `ValidationMessage` - Validation message with severity

### Exceptions

- `EduDexException` - Base exception
- `AuthenticationException` - 401/403 errors
- `ValidationException` - 400 validation errors
- `ApiException` - Other HTTP errors

## Documentation

- **[Standalone Usage Guide](docs/STANDALONE_USAGE.md)** - Complete guide for non-SilverStripe projects
- **[SilverStripe Integration](docs/SILVERSTRIPE_INTEGRATION.md)** - SilverStripe-specific setup and usage
- **[Implementation Details](docs/IMPLEMENTATION.md)** - Technical implementation overview
- **[Working Examples](examples/)** - Ready-to-run code examples

## Requirements

- PHP 8.1 or higher
- GuzzleHTTP ^7.0
- PSR-3 Logger interface

**Optional:**
- SilverStripe Framework ^5.0 (for SilverStripe integration)

## Testing

```bash
# Install dev dependencies
composer install

# Run tests
composer test

# Run static analysis
composer phpstan

# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## Support

- **API Documentation**: https://api.edudex.nl/data/v1/
- **Issues**: https://github.com/restruct/edudex-api-client/issues
- **Discussions**: https://github.com/restruct/edudex-api-client/discussions

## License

This library is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- **Mic** (Restruct) - Lead developer
- **Claude** (Anthropic) - AI co-author and code generation
- EDU-DEX API by WebHare BV

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and release notes.
