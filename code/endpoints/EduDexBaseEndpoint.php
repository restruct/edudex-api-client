<?php

use Psr\Log\NullLogger;

/**
 * Base class for all API endpoint classes
 *
 * Provides common functionality for making requests and handling responses
 */
abstract class EduDexBaseEndpoint
{
    /**
     * HTTP client
     *
     * @var EduDexClientInterface
     */
    protected $client;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param EduDexClientInterface $client
     * @param LoggerInterface|null $logger
     */
    public function __construct($client, $logger = null)
    {
        $this->client = $client;
        $this->logger = isset($logger) ? $logger : new NullLogger();
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
    protected function sendGet($path, $query = array(), $headers = array())
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
    protected function sendPost($path, $data = array(), $headers = array())
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
    protected function sendPut($path, $data = array(), $headers = array())
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
    protected function sendPatch($path, $data = array(), $headers = array())
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
    protected function sendDelete($path, $headers = array())
    {
        return $this->client->delete($path, $headers);
    }

    /**
     * Hydrate a model from response data
     *
     * @template T of EduDexModel
     * @param class-string<T> $modelClass
     * @param array $data
     * @return EduDexModel
     */
    protected function hydrateModel($modelClass, $data)
    {
        return $modelClass::fromArray($data);
    }

    /**
     * Hydrate an array of models from response data
     *
     * @template T of EduDexModel
     * @param class-string<T> $modelClass
     * @param array $items
     * @return T[]
     */
    protected function hydrateModels($modelClass, $items)
    {
        return array_map(
            function($data) use ($modelClass) {
                return $this->hydrateModel($modelClass, $data);
            },
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
    protected function extractListItems($response, $key)
    {
        return isset($response[$key]) ? $response[$key] : array();
    }

    /**
     * Build query parameters, filtering out null values
     *
     * @param array $params
     * @return array
     */
    protected function buildQuery($params)
    {
        return array_filter($params, function($value) {
            return $value !== null;
        });
    }

    /**
     * Validate required parameters
     *
     * @param array $params Array of parameter name => value
     * @param array $required Array of required parameter names
     * @throws EduDexException
     * @return void
     */
    protected function validateRequired($params, $required)
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
    protected function debug($message, $context = array())
    {
        $this->logger->debug($message, $context);
    }
}
