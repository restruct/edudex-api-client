<?php

/**
 * Exception thrown when validation fails
 */
class EduDexValidationException extends EduDexException
{
    /**
     * Validation error messages
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Validation warning messages
     *
     * @var array
     */
    protected $warnings = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param array $messages Validation messages from API
     * @param int $code
     */
    public function __construct($message = 'Validation failed', $messages = array(), $code = 400)
    {
        parent::__construct($message, $code);

        // Separate errors and warnings
        foreach ($messages as $msg) {
            $severity = isset($msg['severity']) ? $msg['severity'] : 'error';
            if ($severity === 'error') {
                $this->errors[] = $msg;
            } else {
                $this->warnings[] = $msg;
            }
        }
    }

    /**
     * Get validation error messages
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get validation warning messages
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Check if there are any errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings
     *
     * @return bool
     */
    public function hasWarnings()
    {
        return !empty($this->warnings);
    }

    /**
     * Get all messages (errors and warnings)
     *
     * @return array
     */
    public function getAllMessages()
    {
        return array_merge($this->errors, $this->warnings);
    }

    /**
     * Get formatted error messages as strings
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return array_map(function($err) {
            return isset($err['message']) ? $err['message'] : '';
        }, $this->errors);
    }

    /**
     * Get formatted warning messages as strings
     *
     * @return array
     */
    public function getWarningMessages()
    {
        return array_map(function($warn) {
            return isset($warn['message']) ? $warn['message'] : '';
        }, $this->warnings);
    }
}
