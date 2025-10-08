<?php

namespace Restruct\EduDex\Http;

use Restruct\EduDex\Exceptions\ApiException;
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Guzzle HTTP client implementation
 *
 * @package Restruct\EduDex\Http
 */
class GuzzleClient implements ClientInterface
{
    /**
     * Guzzle HTTP client
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Base URL for API requests
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Bearer token for authentication
     *
     * @var string
     */
    protected string $bearerToken;

    /**
     * Constructor
     *
     * @param string $baseUrl Base API URL
     * @param string $bearerToken Bearer authentication token
     * @param array $config Additional Guzzle configuration
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        string $baseUrl,
        string $bearerToken,
        array $config = [],
        ?LoggerInterface $logger = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->bearerToken = $bearerToken;
        $this->logger = $logger ?? new NullLogger();

        $defaultConfig = [
            'base_uri' => $this->baseUrl . '/',
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
            ],
        ];

        $this->client = new Client(array_merge($defaultConfig, $config));
    }

    /**
     * @inheritDoc
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $path, [
            'query' => $query,
            'headers' => $headers,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function post(string $path, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $path, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function put(string $path, array $data = [], array $headers = []): array
    {
        return $this->request('PUT', $path, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function patch(string $path, array $data = [], array $headers = []): array
    {
        return $this->request('PATCH', $path, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path, array $headers = []): array
    {
        return $this->request('DELETE', $path, [
            'headers' => $headers,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $path = ltrim($path, '/');

        try {
            $this->logger->debug("EduDex API Request: {$method} {$path}", [
                'options' => $options,
            ]);

            $response = $this->client->request($method, $path, $options);

            $data = $this->parseResponse($response);

            $this->logger->debug("EduDex API Response: {$method} {$path}", [
                'status' => $response->getStatusCode(),
                'data' => $data,
            ]);

            return $data;
        } catch (GuzzleException $e) {
            $this->logger->error("EduDex API Error: {$method} {$path}", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw $this->handleException($e);
        }
    }

    /**
     * Parse JSON response from API
     *
     * @param ResponseInterface $response
     * @return array
     * @throws EduDexException
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        // Handle empty responses (e.g., from DELETE)
        if (empty($body)) {
            return [];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EduDexException(
                'Invalid JSON response: ' . json_last_error_msg(),
                $response->getStatusCode(),
                null,
                ['body' => $body]
            );
        }

        return $data ?? [];
    }

    /**
     * Handle Guzzle exceptions and convert to EduDex exceptions
     *
     * @param GuzzleException $e
     * @return EduDexException
     */
    protected function handleException(GuzzleException $e): EduDexException
    {
        // Handle network errors (no response)
        if (!$e instanceof RequestException || !$e->hasResponse()) {
            return ApiException::networkError($e->getMessage());
        }

        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true) ?? [];

        $message = $data['error'] ?? $data['message'] ?? $e->getMessage();
        $code = $data['code'] ?? null;

        // Handle specific error types
        return match (true) {
            // Authentication errors
            $statusCode === 401 => AuthenticationException::invalidCredentials($message),
            $statusCode === 403 => AuthenticationException::forbidden($message),

            // Validation errors
            $statusCode === 400 && isset($data['messages']) => new ValidationException(
                $message,
                $data['messages'],
                $statusCode
            ),

            // Not found
            $statusCode === 404 => ApiException::notFound($message),

            // Timeout
            $statusCode === 408 => ApiException::timeout(),

            // Server errors
            $statusCode >= 500 => ApiException::serverError($message),

            // Other client errors
            $statusCode >= 400 && $statusCode < 500 => ApiException::badRequest($message, $data),

            // Fallback
            default => new EduDexException($message, $statusCode, $e, $data),
        };
    }

    /**
     * Get the Guzzle client instance
     *
     * @return Client
     */
    public function getGuzzleClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
