<?php

namespace Restruct\EduDex\Integration\SilverStripe;

use Psr\Log\LoggerInterface;
use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\AuthenticationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;

/**
 * SilverStripe-specific EduDex API Client
 *
 * Wraps the base Client with SilverStripe integration features
 * Use this in SilverStripe projects for Config API and Injector support
 *
 * @package Restruct\EduDex\Integration\SilverStripe
 */
class SilverStripeClient extends Client
{
    use Injectable;
    use Configurable;

    /**
     * Default API base URL
     *
     * @config
     * @var string
     */
    private static string $api_base_url = 'https://api.edudex.nl/data/v1/';

    /**
     * Request timeout in seconds
     *
     * @config
     * @var int
     */
    private static int $timeout = 30;

    /**
     * Enable debug logging
     *
     * @config
     * @var bool
     */
    private static bool $debug = false;

    /**
     * Cache TTL for API responses (seconds)
     *
     * @config
     * @var int
     */
    private static int $cache_ttl = 3600;

    /**
     * Constructor
     *
     * Reads configuration from SilverStripe Config API
     *
     * Bearer token must be provided via constructor parameter or EDUDEX_API_TOKEN environment variable.
     * It cannot be set via Config API for security reasons.
     *
     * @param string|null $bearerToken Bearer token for authentication
     * @param string|null $baseUrl API base URL
     * @param array $config Additional HTTP client configuration
     * @param LoggerInterface|null $logger Optional logger instance
     * @throws AuthenticationException
     */
    public function __construct(
        ?string $bearerToken = null,
        ?string $baseUrl = null,
        array $config = [],
        ?LoggerInterface $logger = null
    ) {
        // Resolve bearer token: parameter > environment variable only
        $bearerToken = $bearerToken
            ?? Environment::getEnv('EDUDEX_API_TOKEN')
            ?: null;

        // Resolve base URL: parameter > SilverStripe config > default
        $baseUrl = $baseUrl
            ?? static::config()->get('api_base_url')
            ?? self::DEFAULT_BASE_URL;

        // Merge SilverStripe config with passed config
        $ssConfig = [
            'timeout' => static::config()->get('timeout') ?? self::DEFAULT_TIMEOUT,
            'debug' => static::config()->get('debug') ?? false,
            'cache_ttl' => static::config()->get('cache_ttl') ?? 3600,
        ];

        $config = array_merge($ssConfig, $config);

        // Call parent constructor
        parent::__construct($bearerToken, $baseUrl, $config, $logger);
    }

    /**
     * Get debug mode setting
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->getConfig('debug', false);
    }

    /**
     * Get cache TTL setting
     *
     * @return int
     */
    public function getCacheTTL(): int
    {
        return $this->getConfig('cache_ttl', 3600);
    }
}
