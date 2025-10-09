<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Guzzle HTTP client implementation
 */
class EduDexGuzzleClient implements EduDexClientInterface
{
    /**
     * Guzzle HTTP client
     *
     * @var Client
     */
    protected $client;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Base URL for API requests
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Bearer token for authentication
     *
     * @var string
     */
    protected $bearerToken;

    /**
     * Constructor
     *
     * @param string $baseUrl Base API URL
     * @param string $bearerToken Bearer authentication token
     * @param array $config Additional Guzzle configuration
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        $baseUrl,
        $bearerToken,
        $config = array(),
        $logger = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->bearerToken = $bearerToken;
        $this->logger = $logger !== null ? $logger : new NullLogger();

        $defaultConfig = array(
            'base_uri' => $this->baseUrl . '/',
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
            ),
        );

        $this->client = new Client(array_merge($defaultConfig, $config));
    }

    /**
     * @inheritDoc
     */
    public function get($path, $query = array(), $headers = array())
    {
        return $this->request('GET', $path, array(
            'query' => $query,
            'headers' => $headers,
        ));
    }

    /**
     * @inheritDoc
     */
    public function post($path, $data = array(), $headers = array())
    {
        return $this->request('POST', $path, array(
            'json' => $data,
            'headers' => $headers,
        ));
    }

    /**
     * @inheritDoc
     */
    public function put($path, $data = array(), $headers = array())
    {
        return $this->request('PUT', $path, array(
            'json' => $data,
            'headers' => $headers,
        ));
    }

    /**
     * @inheritDoc
     */
    public function patch($path, $data = array(), $headers = array())
    {
        return $this->request('PATCH', $path, array(
            'json' => $data,
            'headers' => $headers,
        ));
    }

    /**
     * @inheritDoc
     */
    public function delete($path, $headers = array())
    {
        return $this->request('DELETE', $path, array(
            'headers' => $headers,
        ));
    }

    /**
     * @inheritDoc
     */
    public function request($method, $path, $options = array())
    {
        $path = ltrim($path, '/');

        try {
            $this->logger->debug("EduDex API Request: {$method} {$path}", array(
                'options' => $options,
            ));

            $response = $this->client->request($method, $path, $options);

            $data = $this->parseResponse($response);

            $this->logger->debug("EduDex API Response: {$method} {$path}", array(
                'status' => $response->getStatusCode(),
                'data' => $data,
            ));

            return $data;
        } catch (GuzzleException $e) {
            $this->logger->error("EduDex API Error: {$method} {$path}", array(
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ));

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
    protected function parseResponse($response)
    {
        $body = (string) $response->getBody();

        // Handle empty responses (e.g., from DELETE)
        if (empty($body)) {
            return array();
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EduDexException(
                'Invalid JSON response: ' . json_last_error_msg(),
                $response->getStatusCode(),
                null,
                array('body' => $body)
            );
        }

        return $data !== null ? $data : array();
    }

    /**
     * Handle Guzzle exceptions and convert to EduDex exceptions
     *
     * @param GuzzleException $e
     * @return EduDexException
     */
    protected function handleException($e)
    {
        // Handle network errors (no response)
        if (!$e instanceof RequestException || !$e->hasResponse()) {
            return ApiException::networkError($e->getMessage());
        }

        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        if ($data === null) {
            $data = array();
        }

        $message = isset($data['error']) ? $data['error'] : (isset($data['message']) ? $data['message'] : $e->getMessage());
        $code = isset($data['code']) ? $data['code'] : null;

        // Handle specific error types
        // Authentication errors
        if ($statusCode === 401) {
            return AuthenticationException::invalidCredentials($message);
        }
        if ($statusCode === 403) {
            return AuthenticationException::forbidden($message);
        }

        // Validation errors
        if ($statusCode === 400 && isset($data['messages'])) {
            return new ValidationException(
                $message,
                $data['messages'],
                $statusCode
            );
        }

        // Not found
        if ($statusCode === 404) {
            return ApiException::notFound($message);
        }

        // Timeout
        if ($statusCode === 408) {
            return ApiException::timeout();
        }

        // Server errors
        if ($statusCode >= 500) {
            return ApiException::serverError($message);
        }

        // Other client errors
        if ($statusCode >= 400 && $statusCode < 500) {
            return ApiException::badRequest($message, $data);
        }

        // Fallback
        return new EduDexException($message, $statusCode, $e, $data);
    }

    /**
     * Get the Guzzle client instance
     *
     * @return Client
     */
    public function getGuzzleClient()
    {
        return $this->client;
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
