<?php

/**
 * Exception thrown when authentication fails (401/403)
 */
class EduDexAuthenticationException extends EduDexException
{
    /**
     * Create an authentication exception for invalid credentials
     *
     * @param string $message
     * @return EduDexAuthenticationException
     */
    public static function invalidCredentials($message = 'Invalid API credentials')
    {
        return new self($message, 401);
    }

    /**
     * Create an authentication exception for insufficient permissions
     *
     * @param string $message
     * @return EduDexAuthenticationException
     */
    public static function forbidden($message = 'Insufficient permissions')
    {
        return new self($message, 403);
    }

    /**
     * Create an authentication exception for missing token
     *
     * @return EduDexAuthenticationException
     */
    public static function missingToken()
    {
        return new self('Bearer token is required', 401);
    }
}
