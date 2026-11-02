# EDU-DEX API client for PHP

Read and publish training programs, catalogs, webhooks and accreditations in the
[EDU-DEX Data API](https://api.edudex.nl/data/v1/) from PHP: an endpoint class per API area, typed
models with multi-language strings, validation before you submit, and exceptions per kind of
failure. Framework-agnostic, with optional Silverstripe 5 integration that adds an EDU-DEX settings
tab to the CMS.

*Maintained by [Restruct](https://github.com/restruct). If this module saves you time, you can
[support ongoing maintenance](https://github.com/sponsors/restruct).*

## The EDU-DEX Data API is an OpenAPI spec, not a PHP library

EDU-DEX is the open data standard for exchanging and publishing training course information. Its
Data API is described by a large OpenAPI specification: bearer-token authentication, JSON bodies
with localized strings, catalogs that are static or filter-based, and validation responses with
error and warning messages per field. Talking to it directly means writing the HTTP calls, the
JSON mapping and the error handling yourself.

This library wraps it: `$client->suppliers()->getProgram(...)` returns a `Program` model,
`$client->validations()->validateProgram(...)` returns a `ValidationResult` you can loop over, and
an HTTP error arrives as an `AuthenticationException`, `ValidationException` or `ApiException`.

## Programs, catalogs and validation from PHP

- **API coverage**: organizations, suppliers, accreditors, programs and validations, following the
  bundled EDU-DEX OpenAPI specification
- **Framework agnostic**: works with any PHP 8.1+ project
- **Optional Silverstripe integration**: Config API, a SiteConfig extension and an Injector mapping
- **Clean architecture**: PSR-4 autoloading, typed models, endpoint classes
- **Type safe**: PHP 8.1+ with strict type hints and return types
- **Smart models**: automatic hydration, serialization and type casting
- **Localization**: built-in `LocalizedString` helper for multi-language content
- **Validation**: pre-submission validation for programs, institute metadata and discounts
- **Secure**: bearer token authentication with environment variable support
- **Documented**: PHPDoc blocks throughout and usage guides in `docs/`

### Quick start (any PHP project)

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

The constructor throws an `AuthenticationException` when no token is passed, configured or set in
`EDUDEX_API_TOKEN`.

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

The token is taken from the argument, then `bearer_token` in the config array, then
`EDUDEX_API_TOKEN`; the base URL from the argument, then `api_base_url`, then the default
`https://api.edudex.nl/data/v1/`. The timeout defaults to 30 seconds. The config array is also
passed to Guzzle, so other Guzzle client options (`verify`, `proxy`, `connect_timeout`, `handler`, ...)
work there too, except the package's own keys (`bearer_token`, `api_base_url`, `debug`, `cache_ttl`),
which never reach Guzzle. A `headers` key adds to the default headers; it cannot remove the
`Authorization` header or replace the bearer token. Pass a PSR-3 logger as the last
argument of `new Client()` or `Client::fromConfig()` to log every request and response (debug
level) and every failed request (error level).

### Working with organizations

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

### Managing programs

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

`upsertProgram()` and `deleteProgram()` address `suppliers/{orgUnitId}/programs/{programId}/{clientId}`;
pass `'public'` as the client id for the public catalog. (Before 1.1.0 they dropped the client id and
called a path the spec defines for GET only, [#1](https://github.com/restruct/edudex-api-client/issues/1).)

> **Known issue ([#3](https://github.com/restruct/edudex-api-client/issues/3)):** the API answers a
> program PUT with a status object (`status`, `validationMessages`, `lastChange`), not the program,
> so the `Program` that `upsertProgram()` returns carries none of the program fields.

### Validating data

```php
// Validate program before submission
$result = $client->validations()->validateProgram($programData);

if ($result->isValid()) {
    echo "Validation passed\n";

    // Submit the program
    $client->suppliers()->upsertProgram(...);
} else {
    echo "Validation failed:\n";

    foreach ($result->getErrors() as $error) {
        echo "  - {$error->message}";
        if ($error->contextPath) {
            echo " (at {$error->contextPath})";
        }
        echo "\n";
    }
}
```

> **Known issue ([#3](https://github.com/restruct/edudex-api-client/issues/3)):** per the bundled
> spec, the validation response carries `validates` and `validationMessages` (with `level`), while
> `ValidationResult` reads `messages` (with `severity`). Against a spec-shaped response
> `isValid()` is therefore always `true`. Until this is fixed, read the raw response instead:
> `$client->getHttpClient()->post('validations/programs', ['data' => $programData])['validates']`.

`validateInstitute()` and `validateDiscounts()` work the same way. A `ValidationResult` also has
`getWarnings()`, `getInfo()`, `getErrorCount()`/`getWarningCount()` and
`getErrorMessages()`/`getWarningMessages()` (plain strings).

### Bulk operations

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

`bulk()` also takes `$viewerOrgUnitId` (sent as `viewer`; the API needs a special token for it) and
`$viewDiscountsForOrgUnitId` (sent as `viewDiscountsFor`, to apply that client's discounts). Before
1.1.0 neither was sent ([#1](https://github.com/restruct/edudex-api-client/issues/1)).

> **Known issue ([#3](https://github.com/restruct/edudex-api-client/issues/3)):** per the bundled
> spec, the bulk response lists every program under `programs`, each with `data` or `error`;
> `getSuccessful()` and `getFailed()` read `successful`/`failed` keys and return empty arrays for
> that shape. Read `$response['programs']` directly until this is fixed.

### Error handling

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

A `ValidationException` is thrown for a 400 response that carries validation messages; its
`getErrors()` and `getWarnings()` return the API's message arrays. Other 4xx responses, 404, 408,
5xx and network errors (no response at all) become an `ApiException`. All of them extend `EduDexException`.

### Silverstripe integration

For Silverstripe projects, use the optional integration layer:

```bash
composer require restruct/edudex-api-client
composer require silverstripe/framework:^5.0
```

#### Configuration

The package ships its own `_config/edudex.yml`, so in a Silverstripe project two things are applied
automatically, without any project config:

- `SiteConfigExtension` is added to `SiteConfig` (an "EduDex" tab under Settings in the CMS; run
  `dev/build` after installing).
- The Injector maps `Restruct\EduDex\Client` to `SilverStripeClient`.

The EduDex tab is shown to administrators only. It holds an API base URL and a bearer token, and
shows a connection status. Opening the tab does not call the API; its "Test connection" link reloads
Settings with `?edudexcheck=1`, which calls `organizations()->list()` once with the configured token,
or `EDUDEX_API_TOKEN`, and reports how many organizations it found or why it failed. The token and
base URL stored there are used by the extension's own `getEduDexClient()`, `getEduDexToken()` and
`getEduDexBaseUrl()` on `SiteConfig`. `getEduDexClient()` builds the client through the Injector, so
it is a `SilverStripeClient` and its `timeout` and `debug` config apply. `SilverStripeClient` itself
does not read the stored values: it takes its token from the constructor argument or
`EDUDEX_API_TOKEN`, and its base URL from the constructor argument or config.

Client settings are optional; override them in your own config, e.g. `app/_config/edudex.yml`:

```yaml
---
Name: app-edudex-client
---
Restruct\EduDex\Integration\SilverStripe\SilverStripeClient:
  api_base_url: 'https://api.edudex.nl/data/v1/'
  timeout: 30
```

`SilverStripeClient` also has a `debug` setting (default `false`). When it is on and no logger is
passed to the constructor, every request and response is logged at debug level to the Silverstripe
logger service (`Psr\Log\LoggerInterface`). It is never passed to Guzzle: before 1.2.0 it was, and
Guzzle's own `debug` option prints the whole transfer, bearer token included, into the output.

`cache_ttl` and `getCacheTTL()` are deprecated since 1.2.0 and will be removed in 2.0: the client has
never cached anything, so the setting had no effect.

To switch the CMS integration off, remove the extension in your project's `app/_config.php`. The
package adds it as an unnamed list entry, which YAML config cannot unset, so this has to be PHP:

```php
use Restruct\EduDex\Integration\SilverStripe\Extensions\SiteConfigExtension;
use SilverStripe\SiteConfig\SiteConfig;

SiteConfig::remove_extension(SiteConfigExtension::class);
```

#### Usage in Silverstripe

```php
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;

// Using singleton
$client = SilverStripeClient::singleton();

// Or via Injector
$client = Injector::inst()->get(SilverStripeClient::class);

// Same API as standalone
$organizations = $client->organizations()->list();

// Or with the token and base URL stored in the CMS (null when no token is set)
$client = SiteConfig::current_site_config()->getEduDexClient();
```

See [docs/SILVERSTRIPE_INTEGRATION.md](docs/SILVERSTRIPE_INTEGRATION.md) for complete documentation.

## Technical

### Version compatibility

| Branch | Package version | PHP | Guzzle | Silverstripe integration |
|--------|-----------------|-----|--------|--------------------------|
| `main` | `1.x` | 8.1+ | 7 | Silverstripe 5 |
| `php74-ss37-backport` | `0.0.x` | 7.4 | 6 | Silverstripe 3.7 |

`composer.json` on each branch is the source of truth for exact constraints.

### Requirements

- PHP 8.1 or higher (PHP 7.4 version in the `php74-ss37-backport` branch)
- GuzzleHTTP ^7.0
- PSR-3 Logger interface (`psr/log`)

**Optional:**
- Silverstripe Framework ^5.0 (for Silverstripe integration; `SiteConfigExtension` extends
  `DataExtension`, which Silverstripe 6 removed, so the integration layer is Silverstripe 5 only)

### Installation

```bash
composer require restruct/edudex-api-client
```

### API reference

#### Endpoints

- **Organizations** (`$client->organizations()`): organizations, static and dynamic catalogs,
  catalog programs, webhooks
- **Suppliers** (`$client->suppliers()`): suppliers, metadata, programs, discounts
- **Accreditors** (`$client->accreditors()`): accreditors and their accreditations
- **Programs** (`$client->programs()`): bulk program retrieval
- **Validations** (`$client->validations()`): validate programs, institute metadata and discounts

#### Models

- `Organization`: organization with roles and accreditations
- `Supplier`: training provider
- `Accreditor`: accreditation organization
- `Accreditation`: supplier accreditation with validity
- `StaticCatalog`: manual program catalog
- `DynamicCatalog`: automatic catalog with filters
- `Webhook`: event notification endpoint
- `Program`: training program/course
- `ValidationResult`: validation response

#### Type helpers

- `LocalizedString`: multi-language content handler
- `ValidationMessage`: validation message with severity

#### Exceptions

- `EduDexException`: base exception
- `AuthenticationException`: 401/403 errors, and a missing token
- `ValidationException`: 400 validation errors
- `ApiException`: other HTTP errors

### Documentation

- **[Standalone Usage Guide](docs/STANDALONE_USAGE.md)**: complete guide for non-Silverstripe projects
- **[Silverstripe Integration](docs/SILVERSTRIPE_INTEGRATION.md)**: Silverstripe-specific setup and usage
- **[Implementation Details](docs/IMPLEMENTATION.md)**: technical implementation overview
- **[Working Examples](examples/)**: ready-to-run code examples

### Testing

The PHPUnit suite in `tests/` drives the real `Client` and `GuzzleClient` against a Guzzle
`MockHandler`, so it makes no network calls and needs no API token. Every request a test sends is
also checked against the bundled `openapi-edudex-spec.json`: the operation (method and path), its
query parameters and the top-level shape of the JSON body.

```bash
# Install dev dependencies
composer install

# Run the standalone suite
composer test
```

`tests/SilverStripe/` needs a booted Silverstripe 5 app and is excluded from the standalone run. CI
(`.github/workflows/ci.yml`) runs it in a throwaway Silverstripe host project, next to the standalone
suite on every PHP version `composer.json` allows.

The static analysis and code style scripts:

```bash
# Run static analysis
composer phpstan

# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

### Support

- **Issues**: https://github.com/restruct/edudex-api-client/issues
- **EDU-DEX Data API Documentation**: https://api.edudex.nl/data/v1/
- **EDU-DEX Data API in Swagger**: https://api.edudex.nl/data/v1/openapi/swagger-ui
- **EDUDOX**: https://www.edudex.nl/edudox/
- **API Getting started**: https://mijn.edudex.nl/api-getting-started/

### License

This library is open-sourced software licensed under the [MIT license](LICENSE).

The MIT license covers this library's own code. The bundled `openapi-edudex-spec.json` is the EDU-DEX Data API specification, published by EDU-DEX; it is included for reference and code generation only, is not covered by the MIT license, and no separate license for it is stated by EDU-DEX. EDU-DEX is an open data standard for exchanging and publishing training course information; see https://www.edudex.nl for its terms of use.

### Credits

- **Michael van Schaik** (Restruct.nl): development
- **Bart van Irsel** (Webium): AI consultant/guru
- EDU-DEX API by WebHare BV
