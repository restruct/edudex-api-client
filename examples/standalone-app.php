<?php
/**
 * Standalone PHP Application Example
 *
 * Demonstrates using the EduDex API client without SilverStripe
 *
 * Usage: php standalone-app.php
 */

require __DIR__ . '/../../../vendor/autoload.php';

use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\EduDexException;

// Configuration
$bearerToken = getenv('EDUDEX_API_TOKEN');

if (!$bearerToken) {
    die("Error: EDUDEX_API_TOKEN environment variable not set\n");
}

echo "=== EduDex API Client - Standalone Example ===\n\n";

try {
    // Initialize client
    echo "1. Initializing client...\n";
    $client = new Client($bearerToken);
    echo "   ✓ Client initialized\n\n";

    // List organizations
    echo "2. Fetching organizations...\n";
    $organizations = $client->organizations()->list();
    echo "   ✓ Found " . count($organizations) . " organizations\n\n";

    // Display organizations
    echo "Organizations:\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($organizations as $org) {
        echo "ID: {$org->id}\n";
        echo "Name: " . $org->getLocalizedName('nl') . "\n";
        echo "Roles: " . implode(', ', $org->roles) . "\n";
        echo "VAT Exempt: " . ($org->vatExempt ? 'Yes' : 'No') . "\n";

        if (!empty($org->accreditations)) {
            echo "Accreditations: " . implode(', ', $org->accreditations) . "\n";
        }

        echo str_repeat('-', 60) . "\n";
    }

    // If there are organizations, get the first one's details
    if (!empty($organizations)) {
        $firstOrg = $organizations[0];

        echo "\n3. Fetching detailed info for: {$firstOrg->getLocalizedName('nl')}\n";
        $orgDetails = $client->organizations()->get($firstOrg->id);

        echo "   ✓ Organization details retrieved\n";
        echo "   Is Supplier: " . ($orgDetails->isSupplier() ? 'Yes' : 'No') . "\n";
        echo "   Is Client: " . ($orgDetails->isClient() ? 'Yes' : 'No') . "\n";
        echo "   Is Intermediary: " . ($orgDetails->isIntermediary() ? 'Yes' : 'No') . "\n";
        echo "   Is Accreditor: " . ($orgDetails->isAccreditor() ? 'Yes' : 'No') . "\n";
    }

    // List suppliers
    echo "\n4. Fetching suppliers...\n";
    $suppliers = $client->suppliers()->list();
    echo "   ✓ Found " . count($suppliers) . " suppliers\n";

    if (!empty($suppliers)) {
        echo "\nFirst 5 suppliers:\n";
        foreach (array_slice($suppliers, 0, 5) as $supplier) {
            echo "  - {$supplier->getLocalizedName('nl')} (ID: {$supplier->id})\n";
        }
    }

    // Validation example
    echo "\n5. Testing validation endpoint...\n";

    $testProgramData = [
        'editor' => 'Test User',
        'format' => 'application/vnd.edudex.program+json',
        'generator' => 'Standalone Example v1.0',
        'lastEdited' => date('c'),
        'programDescriptions' => [
            'title' => [
                'nl' => 'Test Cursus',
                'en' => 'Test Course',
            ],
        ],
    ];

    $validationResult = $client->validations()->validateProgram($testProgramData);

    if ($validationResult->isValid()) {
        echo "   ✓ Validation passed (with " . $validationResult->getWarningCount() . " warnings)\n";
    } else {
        echo "   ✗ Validation failed with " . $validationResult->getErrorCount() . " errors\n";

        if ($validationResult->hasErrors()) {
            echo "\n   Errors:\n";
            foreach ($validationResult->getErrors() as $error) {
                echo "     • {$error->message}";
                if ($error->contextPath) {
                    echo " (at {$error->contextPath})";
                }
                echo "\n";
            }
        }
    }

    echo "\n=== Example completed successfully ===\n";

} catch (EduDexException $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
    echo "   Code: {$e->getCode()}\n";

    if ($e->getCode() === 401 || $e->getCode() === 403) {
        echo "\n   Hint: Check your EDUDEX_API_TOKEN is valid\n";
    }

    exit(1);
}
