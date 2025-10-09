<?php

use Psr\Log\LoggerInterface;

/**
 * EduDex Data API Client
 *
 * Main entry point for interacting with the EduDex API
 * Framework-agnostic - works with any PHP project
 *
 * @version 1.0.2
 * @see https://api.edudex.nl/data/v1/
 */
class EduDexClient
{
    /**
     * Default API base URL
     *
     * @var string
     */
    const DEFAULT_BASE_URL = 'https://api.edudex.nl/data/v1/';

    /**
     * Default timeout in seconds
     *
     * @var int
     */
    const DEFAULT_TIMEOUT = 30;

    /**
     * HTTP client instance
     *
     * @var EduDexClientInterface
     */
    protected $httpClient;

    /**
     * Logger instance
     *
     * @var LoggerInterface|null
     */
    protected $logger = null;

    /**
     * Endpoint instances (lazy loaded)
     *
     * @var array
     */
    protected $endpoints = array();

    /**
     * Configuration array
     *
     * @var array
     */
    protected $config = array();

    /**
     * Constructor
     *
     * @param string|null $bearerToken Bearer token for authentication
     * @param string|null $baseUrl API base URL
     * @param array $config Additional configuration
     * @param LoggerInterface|null $logger Optional logger instance
     * @throws EduDexAuthenticationException
     */
    public function __construct(
        $bearerToken = null,
        $baseUrl = null,
        $config = array(),
        $logger = null
    ) {
        $this->config = $config;

        // Resolve bearer token: parameter > config > environment
        if ($bearerToken === null) {
            $bearerToken = isset($config['bearer_token']) ? $config['bearer_token'] : null;
        }
        if ($bearerToken === null) {
            $bearerToken = getenv('EDUDEX_API_TOKEN');
            if ($bearerToken === false) {
                $bearerToken = null;
            }
        }

        if (empty($bearerToken)) {
            throw EduDexAuthenticationException::missingToken();
        }

        // Resolve base URL: parameter > config > default
        if ($baseUrl === null) {
            $baseUrl = isset($config['api_base_url']) ? $config['api_base_url'] : self::DEFAULT_BASE_URL;
        }

        // Resolve timeout: config > default
        if (!isset($config['timeout'])) {
            $config['timeout'] = self::DEFAULT_TIMEOUT;
        }

        $this->logger = $logger;

        // Create HTTP client
        $this->httpClient = new EduDexGuzzleClient(
            $baseUrl,
            $bearerToken,
            $config,
            $logger
        );
    }

    /**
     * Create client from configuration array
     *
     * @param array $config Configuration array with keys: bearer_token, api_base_url, timeout, etc.
     * @param LoggerInterface|null $logger Optional logger
     * @return EduDexClient
     * @throws EduDexAuthenticationException
     */
    public static function fromConfig($config, $logger = null)
    {
        return new self(
            isset($config['bearer_token']) ? $config['bearer_token'] : null,
            isset($config['api_base_url']) ? $config['api_base_url'] : null,
            $config,
            $logger
        );
    }

    /**
     * Get the HTTP client instance
     *
     * @return EduDexClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Set a custom HTTP client
     *
     * @param EduDexClientInterface $client
     * @return EduDexClient
     */
    public function setHttpClient($client)
    {
        $this->httpClient = $client;
        return $this;
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Organizations endpoint
     *
     * @return EduDexOrganizations
     */
    public function organizations()
    {
        if (!isset($this->endpoints['organizations'])) {
            $this->endpoints['organizations'] = new EduDexOrganizations($this->httpClient, $this->logger);
        }
        return $this->endpoints['organizations'];
    }

    /**
     * Suppliers endpoint
     *
     * @return EduDexSuppliers
     */
    public function suppliers()
    {
        if (!isset($this->endpoints['suppliers'])) {
            $this->endpoints['suppliers'] = new EduDexSuppliers($this->httpClient, $this->logger);
        }
        return $this->endpoints['suppliers'];
    }

    /**
     * Accreditors endpoint
     *
     * @return EduDexAccreditors
     */
    public function accreditors()
    {
        if (!isset($this->endpoints['accreditors'])) {
            $this->endpoints['accreditors'] = new EduDexAccreditors($this->httpClient, $this->logger);
        }
        return $this->endpoints['accreditors'];
    }

    /**
     * Programs endpoint (bulk operations)
     *
     * @return EduDexPrograms
     */
    public function programs()
    {
        if (!isset($this->endpoints['programs'])) {
            $this->endpoints['programs'] = new EduDexPrograms($this->httpClient, $this->logger);
        }
        return $this->endpoints['programs'];
    }

    /**
     * Validations endpoint
     *
     * @return EduDexValidations
     */
    public function validations()
    {
        if (!isset($this->endpoints['validations'])) {
            $this->endpoints['validations'] = new EduDexValidations($this->httpClient, $this->logger);
        }
        return $this->endpoints['validations'];
    }
}
