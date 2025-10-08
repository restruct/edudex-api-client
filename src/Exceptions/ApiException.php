<?php

namespace Restruct\EduDex\Exceptions;

/**
 * Exception for general API errors (4xx/5xx responses)
 *
 * @package Restruct\EduDex\Exceptions
 */
class ApiException extends EduDexException
{
    /**
     * Create an exception for a bad request (400)
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function badRequest(string $message, array $context = []): static
    {
        return new static($message, 400, null, $context);
    }

    /**
     * Create an exception for a not found error (404)
     *
     * @param string $resource
     * @return static
     */
    public static function notFound(string $resource = 'Resource'): static
    {
        return new static("{$resource} not found", 404);
    }

    /**
     * Create an exception for a server error (500)
     *
     * @param string $message
     * @return static
     */
    public static function serverError(string $message = 'Internal server error'): static
    {
        return new static($message, 500);
    }

    /**
     * Create an exception for a network error
     *
     * @param string $message
     * @return static
     */
    public static function networkError(string $message = 'Network error occurred'): static
    {
        return new static($message, 0);
    }

    /**
     * Create an exception for a timeout
     *
     * @return static
     */
    public static function timeout(): static
    {
        return new static('Request timeout', 408);
    }

    /**
     * Check if this is a client error (4xx)
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Check if this is a server error (5xx)
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->code >= 500 && $this->code < 600;
    }
}
