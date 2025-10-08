<?php

namespace Restruct\EduDex\Exceptions;

/**
 * Exception thrown when authentication fails (401/403)
 *
 * @package Restruct\EduDex\Exceptions
 */
class AuthenticationException extends EduDexException
{
    /**
     * Create an authentication exception for invalid credentials
     *
     * @param string $message
     * @return static
     */
    public static function invalidCredentials(string $message = 'Invalid API credentials'): static
    {
        return new static($message, 401);
    }

    /**
     * Create an authentication exception for insufficient permissions
     *
     * @param string $message
     * @return static
     */
    public static function forbidden(string $message = 'Insufficient permissions'): static
    {
        return new static($message, 403);
    }

    /**
     * Create an authentication exception for missing token
     *
     * @return static
     */
    public static function missingToken(): static
    {
        return new static('Bearer token is required', 401);
    }
}
