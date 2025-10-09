<?php

/**
 * EduDex API Client for SilverStripe 3
 *
 * This configuration file loads all classes in the correct order
 * since SS3 doesn't use PSR-4 autoloading
 */

// Get the base path for this module
$eduDexBasePath = dirname(__FILE__) . '/code';

// Load exceptions first (no dependencies)
require_once $eduDexBasePath . '/exceptions/EduDexException.php';
require_once $eduDexBasePath . '/exceptions/EduDexApiException.php';
require_once $eduDexBasePath . '/exceptions/EduDexAuthenticationException.php';
require_once $eduDexBasePath . '/exceptions/EduDexValidationException.php';

// Load type helpers (no dependencies)
require_once $eduDexBasePath . '/types/LocalizedString.php';
require_once $eduDexBasePath . '/types/ValidationMessage.php';

// Load HTTP client interface and implementation (depends on exceptions)
require_once $eduDexBasePath . '/http/EduDexClientInterface.php';
require_once $eduDexBasePath . '/http/EduDexGuzzleClient.php';

// Load base model (depends on types)
require_once $eduDexBasePath . '/models/EduDexModel.php';

// Load all models (depend on Model base class)
require_once $eduDexBasePath . '/models/Organization.php';
require_once $eduDexBasePath . '/models/Supplier.php';
require_once $eduDexBasePath . '/models/Accreditor.php';
require_once $eduDexBasePath . '/models/Accreditation.php';
require_once $eduDexBasePath . '/models/Program.php';
require_once $eduDexBasePath . '/models/StaticCatalog.php';
require_once $eduDexBasePath . '/models/DynamicCatalog.php';
require_once $eduDexBasePath . '/models/Webhook.php';
require_once $eduDexBasePath . '/models/EduDexValidationResult.php';

// Load base endpoint (depends on HTTP client and models)
require_once $eduDexBasePath . '/endpoints/EduDexBaseEndpoint.php';

// Load all endpoint classes (depend on BaseEndpoint)
require_once $eduDexBasePath . '/endpoints/EduDexOrganizations.php';
require_once $eduDexBasePath . '/endpoints/EduDexSuppliers.php';
require_once $eduDexBasePath . '/endpoints/EduDexAccreditors.php';
require_once $eduDexBasePath . '/endpoints/EduDexPrograms.php';
require_once $eduDexBasePath . '/endpoints/EduDexValidations.php';

// Load main client (depends on everything)
require_once $eduDexBasePath . '/EduDexClient.php';

// Load SilverStripe integration classes (optional, depend on main client)
require_once $eduDexBasePath . '/integration/EduDexSilverStripeClient.php';
require_once $eduDexBasePath . '/integration/EduDexSiteConfigExtension.php';
