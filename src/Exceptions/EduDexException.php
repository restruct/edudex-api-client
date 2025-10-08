<?php

namespace Restruct\EduDex\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all EduDex API errors
 *
 * @package Restruct\EduDex\Exceptions
 */
class EduDexException extends Exception
{
    /**
     * Additional context data about the error
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code (typically HTTP status code)
     * @param Throwable|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a specific context value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Check if context has a specific key
     *
     * @param string $key
     * @return bool
     */
    public function hasContextKey(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }
}
