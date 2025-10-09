<?php

/**
 * SilverStripe-specific EduDex API Client
 *
 * Wraps the base Client with SilverStripe 3 integration features
 * Use this in SilverStripe projects for Config API support
 */
class EduDexSilverStripeClient extends EduDexClient
{
    /**
     * Default API base URL
     *
     * @config
     * @var string
     */
    private static $api_base_url = 'https://api.edudex.nl/data/v1/';

    /**
     * Request timeout in seconds
     *
     * @config
     * @var int
     */
    private static $timeout = 30;

    /**
     * Enable debug logging
     *
     * @config
     * @var bool
     */
    private static $debug = false;

    /**
     * Cache TTL for API responses (seconds)
     *
     * @config
     * @var int
     */
    private static $cache_ttl = 3600;

    /**
     * Constructor
     *
     * Reads configuration from SilverStripe Config API and constants
     *
     * Bearer token priority:
     * 1. Constructor parameter
     * 2. EDUDEX_API_TOKEN constant (defined in _ss_environment.php)
     * 3. SiteConfig database field (via getSiteConfig())
     *
     * @param string|null $bearerToken Bearer token for authentication
     * @param string|null $baseUrl API base URL
     * @param array $config Additional HTTP client configuration
     * @param LoggerInterface|null $logger Optional logger instance
     * @throws EduDexAuthenticationException
     */
    public function __construct(
        $bearerToken = null,
        $baseUrl = null,
        $config = array(),
        $logger = null
    ) {
        // Resolve bearer token: parameter > constant > SiteConfig
        if ($bearerToken === null) {
            if (defined('EDUDEX_API_TOKEN')) {
                $bearerToken = EDUDEX_API_TOKEN;
            } elseif (class_exists('SiteConfig')) {
                $siteConfig = SiteConfig::current_site_config();
                if ($siteConfig->hasMethod('getEduDexToken')) {
                    $bearerToken = $siteConfig->getEduDexToken();
                }
            }
        }

        // Resolve base URL: parameter > SilverStripe config > SiteConfig > default
        if ($baseUrl === null) {
            $baseUrl = Config::inst()->get('EduDexSilverStripeClient', 'api_base_url');
            if (empty($baseUrl) && class_exists('SiteConfig')) {
                $siteConfig = SiteConfig::current_site_config();
                if ($siteConfig->hasMethod('getEduDexBaseUrl')) {
                    $baseUrl = $siteConfig->getEduDexBaseUrl();
                }
            }
            if (empty($baseUrl)) {
                $baseUrl = self::DEFAULT_BASE_URL;
            }
        }

        // Merge SilverStripe config with passed config
        $ssConfig = array(
            'timeout' => Config::inst()->get('EduDexSilverStripeClient', 'timeout'),
            'debug' => Config::inst()->get('EduDexSilverStripeClient', 'debug'),
            'cache_ttl' => Config::inst()->get('EduDexSilverStripeClient', 'cache_ttl'),
        );

        // Filter out null values
        $ssConfig = array_filter($ssConfig, function($value) {
            return $value !== null;
        });

        $config = array_merge($ssConfig, $config);

        // Call parent constructor
        parent::__construct($bearerToken, $baseUrl, $config, $logger);
    }

    /**
     * Create instance using singleton pattern
     *
     * @return EduDexSilverStripeClient
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Get debug mode setting
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool) $this->getConfig('debug', false);
    }

    /**
     * Get cache TTL setting
     *
     * @return int
     */
    public function getCacheTTL()
    {
        return (int) $this->getConfig('cache_ttl', 3600);
    }
}
