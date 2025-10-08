<?php

namespace Restruct\EduDex\Endpoints;

use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Http\ClientInterface;
use Restruct\EduDex\Models\Model;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base class for all API endpoint classes
 *
 * Provides common functionality for making requests and handling responses
 *
 * @package Restruct\EduDex\Endpoints
 */
abstract class BaseEndpoint
{
    /**
     * HTTP client
     *
     * @var ClientInterface
     */
    protected ClientInterface $client;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param ClientInterface $client
     * @param LoggerInterface|null $logger
     */
    public function __construct(ClientInterface $client, ?LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Send a GET request
     *
     * @param string $path
     * @param array $query
     * @param array $headers
     * @return array
     * @throws EduDexException
     */
    protected function sendGet(string $path, array $query = [], array $headers = []): array
    {
        return $this->client->get($path, $query, $headers);
    }

    /**
     * Send a POST request
     *
     * @param string $path
     * @param array $data
     * @param array $headers
     * @return array
     * @throws EduDexException
     */
    protected function sendPost(string $path, array $data = [], array $headers = []): array
    {
        return $this->client->post($path, $data, $headers);
    }

    /**
     * Send a PUT request
     *
     * @param string $path
     * @param array $data
     * @param array $headers
     * @return array
     * @throws EduDexException
     */
    protected function sendPut(string $path, array $data = [], array $headers = []): array
    {
        return $this->client->put($path, $data, $headers);
    }

    /**
     * Send a PATCH request
     *
     * @param string $path
     * @param array $data
     * @param array $headers
     * @return array
     * @throws EduDexException
     */
    protected function sendPatch(string $path, array $data = [], array $headers = []): array
    {
        return $this->client->patch($path, $data, $headers);
    }

    /**
     * Send a DELETE request
     *
     * @param string $path
     * @param array $headers
     * @return array
     * @throws EduDexException
     */
    protected function sendDelete(string $path, array $headers = []): array
    {
        return $this->client->delete($path, $headers);
    }

    /**
     * Hydrate a model from response data
     *
     * @template T of Model
     * @param class-string<T> $modelClass
     * @param array $data
     * @return T
     */
    protected function hydrateModel(string $modelClass, array $data): Model
    {
        return $modelClass::fromArray($data);
    }

    /**
     * Hydrate an array of models from response data
     *
     * @template T of Model
     * @param class-string<T> $modelClass
     * @param array $items
     * @return T[]
     */
    protected function hydrateModels(string $modelClass, array $items): array
    {
        return array_map(
            fn($data) => $this->hydrateModel($modelClass, $data),
            $items
        );
    }

    /**
     * Extract items from a list response
     *
     * Many list endpoints return data in a wrapper like:
     * {"organizations": [...]} or {"suppliers": [...]}
     *
     * @param array $response
     * @param string $key
     * @return array
     */
    protected function extractListItems(array $response, string $key): array
    {
        return $response[$key] ?? [];
    }

    /**
     * Build query parameters, filtering out null values
     *
     * @param array $params
     * @return array
     */
    protected function buildQuery(array $params): array
    {
        return array_filter($params, fn($value) => $value !== null);
    }

    /**
     * Validate required parameters
     *
     * @param array $params Array of parameter name => value
     * @param array $required Array of required parameter names
     * @throws EduDexException
     * @return void
     */
    protected function validateRequired(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || $params[$param] === null || $params[$param] === '') {
                throw new EduDexException("Required parameter '{$param}' is missing");
            }
        }
    }

    /**
     * Log debug information
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
