<?php

/**
 * Base exception for all EduDex API errors
 */
class EduDexException extends Exception
{
    /**
     * Additional context data about the error
     *
     * @var array
     */
    protected $context = [];

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code (typically HTTP status code)
     * @param Throwable|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        $message = '',
        $code = 0,
        $previous = null,
        $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context data
     *
     * @return array
     */
    public function getContext()
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
    public function getContextValue($key, $default = null)
    {
        return isset($this->context[$key]) ? $this->context[$key] : $default;
    }

    /**
     * Check if context has a specific key
     *
     * @param string $key
     * @return bool
     */
    public function hasContextKey($key)
    {
        return array_key_exists($key, $this->context);
    }
}
