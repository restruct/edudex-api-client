<?php

/**
 * Exception for general API errors (4xx/5xx responses)
 */
class EduDexApiException extends EduDexException
{
    /**
     * Create an exception for a bad request (400)
     *
     * @param string $message
     * @param array $context
     * @return EduDexApiException
     */
    public static function badRequest($message, $context = array())
    {
        return new self($message, 400, null, $context);
    }

    /**
     * Create an exception for a not found error (404)
     *
     * @param string $resource
     * @return EduDexApiException
     */
    public static function notFound($resource = 'Resource')
    {
        return new self("{$resource} not found", 404);
    }

    /**
     * Create an exception for a server error (500)
     *
     * @param string $message
     * @return EduDexApiException
     */
    public static function serverError($message = 'Internal server error')
    {
        return new self($message, 500);
    }

    /**
     * Create an exception for a network error
     *
     * @param string $message
     * @return EduDexApiException
     */
    public static function networkError($message = 'Network error occurred')
    {
        return new self($message, 0);
    }

    /**
     * Create an exception for a timeout
     *
     * @return EduDexApiException
     */
    public static function timeout()
    {
        return new self('Request timeout', 408);
    }

    /**
     * Check if this is a client error (4xx)
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->code >= 400 && $this->code < 500;
    }

    /**
     * Check if this is a server error (5xx)
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->code >= 500 && $this->code < 600;
    }
}
