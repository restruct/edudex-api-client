<?php

namespace Restruct\EduDex;

use Restruct\EduDex\Endpoints\Accreditors;
use Restruct\EduDex\Endpoints\Organizations;
use Restruct\EduDex\Endpoints\Programs;
use Restruct\EduDex\Endpoints\Suppliers;
use Restruct\EduDex\Endpoints\Validations;
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Http\ClientInterface;
use Restruct\EduDex\Http\GuzzleClient;
use Psr\Log\LoggerInterface;

/**
 * EduDex Data API Client
 *
 * Main entry point for interacting with the EduDex API
 * Framework-agnostic - works with any PHP project
 *
 * @package Restruct\EduDex
 * @version 1.0.2
 * @see https://api.edudex.nl/data/v1/
 */
class Client
{
    /**
     * Default API base URL
     *
     * @var string
     */
    public const DEFAULT_BASE_URL = 'https://api.edudex.nl/data/v1/';

    /**
     * Default timeout in seconds
     *
     * @var int
     */
    public const DEFAULT_TIMEOUT = 30;

    /**
     * HTTP client instance
     *
     * @var ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * Logger instance
     *
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger = null;

    /**
     * Endpoint instances (lazy loaded)
     *
     * @var array
     */
    protected array $endpoints = [];

    /**
     * Configuration array
     *
     * @var array
     */
    protected array $config = [];

    /**
     * Constructor
     *
     * @param string|null $bearerToken Bearer token for authentication
     * @param string|null $baseUrl API base URL
     * @param array $config Additional configuration
     * @param LoggerInterface|null $logger Optional logger instance
     * @throws AuthenticationException
     */
    public function __construct(
        ?string $bearerToken = null,
        ?string $baseUrl = null,
        array $config = [],
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;

        // Resolve bearer token: parameter > config > environment
        $bearerToken = $bearerToken
            ?? $config['bearer_token']
            ?? getenv('EDUDEX_API_TOKEN')
            ?: null;

        if (empty($bearerToken)) {
            throw AuthenticationException::missingToken();
        }

        // Resolve base URL: parameter > config > default
        $baseUrl = $baseUrl
            ?? $config['api_base_url']
            ?? self::DEFAULT_BASE_URL;

        // Resolve timeout: config > default
        if (!isset($config['timeout'])) {
            $config['timeout'] = $config['timeout'] ?? self::DEFAULT_TIMEOUT;
        }

        $this->logger = $logger;

        // Create HTTP client
        $this->httpClient = new GuzzleClient(
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
     * @return static
     * @throws AuthenticationException
     */
    public static function fromConfig(array $config, ?LoggerInterface $logger = null): static
    {
        return new static(
            $config['bearer_token'] ?? null,
            $config['api_base_url'] ?? null,
            $config,
            $logger
        );
    }

    /**
     * Get the HTTP client instance
     *
     * @return ClientInterface
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Set a custom HTTP client
     *
     * @param ClientInterface $client
     * @return static
     */
    public function setHttpClient(ClientInterface $client): static
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
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Organizations endpoint
     *
     * @return Organizations
     */
    public function organizations(): Organizations
    {
        return $this->endpoints['organizations']
            ??= new Organizations($this->httpClient, $this->logger);
    }

    /**
     * Suppliers endpoint
     *
     * @return Suppliers
     */
    public function suppliers(): Suppliers
    {
        return $this->endpoints['suppliers']
            ??= new Suppliers($this->httpClient, $this->logger);
    }

    /**
     * Accreditors endpoint
     *
     * @return Accreditors
     */
    public function accreditors(): Accreditors
    {
        return $this->endpoints['accreditors']
            ??= new Accreditors($this->httpClient, $this->logger);
    }

    /**
     * Programs endpoint (bulk operations)
     *
     * @return Programs
     */
    public function programs(): Programs
    {
        return $this->endpoints['programs']
            ??= new Programs($this->httpClient, $this->logger);
    }

    /**
     * Validations endpoint
     *
     * @return Validations
     */
    public function validations(): Validations
    {
        return $this->endpoints['validations']
            ??= new Validations($this->httpClient, $this->logger);
    }
}
