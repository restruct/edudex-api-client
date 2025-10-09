# EduDex API Client (SilverStripe 3 Backport)

PHP 7.4 + SilverStripe 3 compatible version of the EduDex API Client.

This is a backported version of the [edudex-api-client](../edudex-api-client/) module, made compatible with PHP 7.4 and SilverStripe 3.7.

## Requirements

- PHP 7.4+
- SilverStripe 3.7+
- GuzzleHTTP ^6.0 (already included in main project)
- PSR-3 Logger interface

## Installation

Since this is not a Composer package, it's included directly in the project.

The module auto-loads via `_config.php` which includes all necessary class files.

## Usage

### Basic Usage

```php
<?php

// Initialize with bearer token
$client = new EduDexClient('your-jwt-bearer-token');

// Or use constant EDUDEX_API_TOKEN (define in _ss_environment.php)
$client = new EduDexClient();

// Fetch organizations
$organizations = $client->organizations()->list();

foreach ($organizations as $org) {
    echo $org->getLocalizedName('nl') . "\n";
}
```

### SilverStripe 3 Integration

For SilverStripe projects, use the integrated client that supports the Config API and SiteConfig:

```php
// Using the SilverStripe-integrated client
$client = new EduDexSilverStripeClient();

// Or get from SiteConfig
$siteConfig = SiteConfig::current_site_config();
$client = $siteConfig->getEduDexClient();

// Use like normal client
$organizations = $client->organizations()->list();
```

### Configuration

#### Via _ss_environment.php (Recommended)

Add to your `_ss_environment.php`:

```php
// EduDex API Configuration
define('EDUDEX_API_TOKEN', 'your-jwt-bearer-token-here');
```

#### Via SiteConfig (CMS)

1. Go to Settings → EduDex in the CMS
2. Enter your bearer token and optionally customize the API base URL
3. The connection status will be displayed

#### Via YAML Config

Add to `mysite/_config/edudex.yml`:

```yaml
EduDexSilverStripeClient:
  api_base_url: 'https://api.edudex.nl/data/v1/'
  timeout: 30
  debug: false
  cache_ttl: 3600
```

#### Programmatically

```php
// From array
$config = array(
    'bearer_token' => 'your-token',
    'api_base_url' => 'https://api.edudex.nl/data/v1/',
    'timeout' => 30,
);
$client = EduDexClient::fromConfig($config);

// Direct instantiation
define('EDUDEX_API_TOKEN', 'your-token-here');
$client = new EduDexClient();
```

### Working with Organizations

```php
$client = new EduDexClient();

// List all organizations
$organizations = $client->organizations()->list();

// Get specific organization
$org = $client->organizations()->get('organization-id');

echo "Name: " . $org->getLocalizedName('nl') . "\n";
echo "Supplier: " . ($org->isSupplier() ? 'Yes' : 'No') . "\n";
```

### Managing Programs

```php
// List programs for a supplier
$programs = $client->suppliers()->listPrograms('supplier-id');

// Get specific program
$program = $client->suppliers()->getProgram('supplier-id', 'program-id', 'client-id');

echo "Title: " . $program->getTitle('nl') . "\n";

// Create or update program
$programData = array(
    'editor' => 'Admin User',
    'format' => 'application/vnd.edudex.program+json',
    'generator' => 'Vakwijs DataHub',
    'lastEdited' => date('c'),
    'programDescriptions' => array(
        'title' => array('nl' => 'Cursus Titel', 'en' => 'Course Title'),
    ),
    // ... additional program data
);

$client->suppliers()->upsertProgram('supplier-id', 'program-id', 'client-id', $programData);
```

### Validating Data

```php
// Validate program before submission
$result = $client->validations()->validateProgram($programData);

if ($result->isValid()) {
    echo "✓ Validation passed!\n";
    // Submit the program
    $client->suppliers()->upsertProgram(/* ... */);
} else {
    echo "✗ Validation failed:\n";
    foreach ($result->getErrors() as $error) {
        echo "  • " . $error->message . "\n";
    }
}
```

### Error Handling

```php
try {
    $org = $client->organizations()->get('org-id');

} catch (EduDexAuthenticationException $e) {
    // Handle 401/403 errors
    echo "Authentication error: " . $e->getMessage() . "\n";

} catch (EduDexValidationException $e) {
    // Handle validation errors
    foreach ($e->getErrors() as $error) {
        echo "Error: " . $error['message'] . "\n";
    }

} catch (EduDexApiException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage() . "\n";

} catch (EduDexException $e) {
    // Catch-all
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Available Endpoints

- **Organizations** - `$client->organizations()`
- **Suppliers** - `$client->suppliers()`
- **Accreditors** - `$client->accreditors()`
- **Programs** - `$client->programs()` (bulk operations)
- **Validations** - `$client->validations()`

## Differences from Original

This backported version has the following differences from the original PHP 8.1 version:

1. **No namespaces** - All classes use the `EduDex` prefix instead
2. **No type hints** - Method parameters and return types use docblock annotations only
3. **PHP 7.4 syntax** - No PHP 8+ features (no named arguments, match expressions, etc.)
4. **Traditional arrays** - Uses `array()` instead of `[]` syntax
5. **Class loading** - Manual includes via `_config.php` instead of PSR-4 autoloading
6. **Compatibility** - Fully compatible with SilverStripe 3.7 and PHP 7.4

## SilverStripe 3 Features

### SiteConfig Integration

The `EduDexSiteConfigExtension` adds a dedicated "EduDex" tab to SiteConfig:

- **Bearer Token Field** - Store API token in database (encrypted recommended)
- **API Base URL** - Customize API endpoint if needed
- **Connection Status** - Live connection test showing number of organizations
- **Priority System** - Token resolution: SiteConfig → EDUDEX_API_TOKEN constant

### Configuration Priority

Bearer tokens are resolved in this order:
1. Constructor parameter
2. `EDUDEX_API_TOKEN` constant (from `_ss_environment.php`)
3. SiteConfig database field

Base URL is resolved in this order:
1. Constructor parameter
2. YAML config (`EduDexSilverStripeClient.api_base_url`)
3. SiteConfig database field
4. Default (`https://api.edudex.nl/data/v1/`)

### Extension Methods

When you add the extension to SiteConfig, you get these helper methods:

```php
$siteConfig = SiteConfig::current_site_config();

// Get configured token
$token = $siteConfig->getEduDexToken();

// Get configured base URL
$baseUrl = $siteConfig->getEduDexBaseUrl();

// Get a configured client instance
$client = $siteConfig->getEduDexClient();
if ($client) {
    $organizations = $client->organizations()->list();
}
```

## Class Reference

### Main Classes

- `EduDexClient` - Main API client (framework-agnostic)
- `EduDexSilverStripeClient` - SilverStripe-integrated client with Config API support
- `EduDexGuzzleClient` - HTTP client implementation
- `EduDexClientInterface` - HTTP client interface

### SilverStripe Integration

- `EduDexSiteConfigExtension` - Adds EduDex configuration to SiteConfig CMS

### Exceptions

- `EduDexException` - Base exception
- `EduDexAuthenticationException` - 401/403 errors
- `EduDexValidationException` - 400 validation errors
- `EduDexApiException` - Other HTTP errors

### Models

- `EduDexOrganization`
- `EduDexSupplier`
- `EduDexAccreditor`
- `EduDexAccreditation`
- `EduDexProgram`
- `EduDexStaticCatalog`
- `EduDexDynamicCatalog`
- `EduDexWebhook`
- `EduDexValidationResult`

### Type Helpers

- `LocalizedString` - Multi-language content
- `ValidationMessage` - Validation messages

### Endpoints

- `EduDexOrganizations`
- `EduDexSuppliers`
- `EduDexAccreditors`
- `EduDexPrograms`
- `EduDexValidations`

## License

MIT License - Same as original module

## Credits

Backported from [restruct/edudex-api-client](https://github.com/restruct/edudex-api-client) by Restruct + Claude AI
