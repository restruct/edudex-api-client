<?php

namespace Restruct\EduDex\Integration\SilverStripe\Config;

use SilverStripe\Core\Config\Configurable;

/**
 * EduDex API Configuration
 *
 * Configure via YML or environment variables
 *
 * @package Restruct\EduDex\Config
 */
class EduDexConfig
{
    use Configurable;

    /**
     * API base URL
     *
     * @config
     * @var string
     */
    private static string $api_base_url = 'https://api.edudex.nl/data/v1/';

    /**
     * Bearer token for authentication
     * Can use backtick syntax for environment variables: `EDUDEX_API_TOKEN`
     *
     * @config
     * @var string
     */
    private static string $bearer_token = '';

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
     * Cache TTL for API responses (in seconds)
     * Set to 0 to disable caching
     *
     * @config
     * @var int
     */
    private static int $cache_ttl = 3600;

    /**
     * Get the API base URL
     *
     * @return string
     */
    public static function getApiBaseUrl(): string
    {
        return static::config()->get('api_base_url');
    }

    /**
     * Get the bearer token
     *
     * @return string
     */
    public static function getBearerToken(): string
    {
        return static::config()->get('bearer_token');
    }

    /**
     * Get the request timeout
     *
     * @return int
     */
    public static function getTimeout(): int
    {
        return static::config()->get('timeout');
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public static function isDebugEnabled(): bool
    {
        return static::config()->get('debug');
    }

    /**
     * Get cache TTL
     *
     * @return int
     */
    public static function getCacheTTL(): int
    {
        return static::config()->get('cache_ttl');
    }
}
