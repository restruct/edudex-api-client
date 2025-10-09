# SilverStripe 3 Integration Guide

This document explains how to use the EduDex API Client in SilverStripe 3 projects.

## Quick Start

### 1. Configure Your Bearer Token

Add to `_ss_environment.php`:

```php
// EduDex API Configuration
define('EDUDEX_API_TOKEN', 'your-jwt-bearer-token-here');
```

### 2. Use in Your Code

```php
// Simple usage
$client = new EduDexSilverStripeClient();
$organizations = $client->organizations()->list();

// Or via SiteConfig
$siteConfig = SiteConfig::current_site_config();
$client = $siteConfig->getEduDexClient();
```

## Configuration Methods

### Method 1: Constants (Recommended)

Best for security - keeps tokens out of database.

In `_ss_environment.php`:

```php
define('EDUDEX_API_TOKEN', 'your-token-here');
```

### Method 2: CMS (SiteConfig)

Good for non-technical administrators.

1. Log in to the CMS
2. Go to **Settings** → **EduDex** tab
3. Enter your bearer token
4. Optionally customize the API base URL
5. View connection status

### Method 3: YAML Config

Good for environment-specific settings.

In `mysite/_config/edudex.yml`:

```yaml
EduDexSilverStripeClient:
  api_base_url: 'https://api.edudex.nl/data/v1/'
  timeout: 30
  debug: false
  cache_ttl: 3600
```

## Usage Examples

### Basic API Calls

```php
// Create client
$client = new EduDexSilverStripeClient();

// List organizations
$organizations = $client->organizations()->list();
foreach ($organizations as $org) {
    echo $org->getLocalizedName('nl') . "\n";
}

// Get specific organization
$org = $client->organizations()->get('org-id');

// List programs for a supplier
$programs = $client->suppliers()->listPrograms('supplier-id');
```

### Using SiteConfig Helper

```php
class MyController extends Controller
{
    public function index()
    {
        $siteConfig = SiteConfig::current_site_config();
        $client = $siteConfig->getEduDexClient();
        
        if (!$client) {
            return 'EduDex API not configured';
        }
        
        $organizations = $client->organizations()->list();
        
        return $this->customise(array(
            'Organizations' => ArrayList::create($organizations)
        ))->renderWith('MyTemplate');
    }
}
```

### In a DataObject

```php
class MyCourse extends DataObject
{
    private static $db = array(
        'EduDexProgramId' => 'Varchar(100)',
    );
    
    public function syncFromEduDex()
    {
        $client = new EduDexSilverStripeClient();
        
        try {
            $program = $client->suppliers()->getProgram(
                'supplier-id',
                $this->EduDexProgramId,
                'public'
            );
            
            $this->Title = $program->getTitle('nl');
            $this->Content = $program->getDescription('nl');
            $this->write();
            
        } catch (EduDexException $e) {
            SS_Log::log($e->getMessage(), SS_Log::ERR);
        }
    }
}
```

### In a BuildTask

```php
class SyncEduDexDataTask extends BuildTask
{
    protected $title = 'Sync EduDex Course Data';
    
    protected $description = 'Synchronize course data from EduDex API';
    
    public function run($request)
    {
        $client = new EduDexSilverStripeClient();
        
        echo "Fetching organizations...\n";
        $organizations = $client->organizations()->list();
        
        foreach ($organizations as $org) {
            echo "- " . $org->getLocalizedName('nl') . "\n";
            
            if ($org->isSupplier()) {
                $programs = $client->suppliers()->listPrograms($org->orgUnitId);
                echo "  Found " . count($programs) . " programs\n";
            }
        }
        
        echo "Sync complete!\n";
    }
}
```

## Configuration Options

All configuration can be set via YAML:

```yaml
EduDexSilverStripeClient:
  # API endpoint (default: https://api.edudex.nl/data/v1/)
  api_base_url: 'https://api.edudex.nl/data/v1/'
  
  # Request timeout in seconds (default: 30)
  timeout: 30
  
  # Enable debug logging (default: false)
  debug: false
  
  # Cache TTL for API responses in seconds (default: 3600)
  cache_ttl: 3600
```

## Priority Order

### Bearer Token Resolution

1. Constructor parameter
2. `EDUDEX_API_TOKEN` constant
3. SiteConfig database field

### Base URL Resolution

1. Constructor parameter
2. YAML config `api_base_url`
3. SiteConfig database field
4. Default constant

## Security Best Practices

1. **Use Constants for Tokens** - Store bearer tokens in `_ss_environment.php` using `define('EDUDEX_API_TOKEN', '...')` rather than in the database
2. **Restrict CMS Access** - The SiteConfig extension only shows for ADMIN users
3. **Never Commit Tokens** - Add `_ss_environment.php` to `.gitignore`
4. **Use Different Tokens** - Use separate tokens for dev, test, and production environments

## Troubleshooting

### "No bearer token configured"

Make sure you've defined the token:
```php
define('EDUDEX_API_TOKEN', 'your-token');
```

### "Connection failed"

1. Check your bearer token is valid
2. Verify the API base URL is correct
3. Check network connectivity
4. Look at the connection status in Settings → EduDex

### Check Configuration

```php
$client = new EduDexSilverStripeClient();
echo "Base URL: " . $client->getHttpClient()->getBaseUrl() . "\n";
echo "Debug: " . ($client->isDebugEnabled() ? 'Yes' : 'No') . "\n";
echo "Cache TTL: " . $client->getCacheTTL() . "s\n";
```

## API Reference

See the main [README.md](README.md) for complete API documentation.
