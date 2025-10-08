<?php

namespace Restruct\EduDex\Exceptions;

/**
 * Exception thrown when validation fails
 *
 * @package Restruct\EduDex\Exceptions
 */
class ValidationException extends EduDexException
{
    /**
     * Validation error messages
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Validation warning messages
     *
     * @var array
     */
    protected array $warnings = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param array $messages Validation messages from API
     * @param int $code
     */
    public function __construct(string $message = 'Validation failed', array $messages = [], int $code = 400)
    {
        parent::__construct($message, $code);

        // Separate errors and warnings
        foreach ($messages as $msg) {
            if (($msg['severity'] ?? 'error') === 'error') {
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
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warning messages
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if there are any errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get all messages (errors and warnings)
     *
     * @return array
     */
    public function getAllMessages(): array
    {
        return array_merge($this->errors, $this->warnings);
    }

    /**
     * Get formatted error messages as strings
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        return array_map(fn($err) => $err['message'] ?? '', $this->errors);
    }

    /**
     * Get formatted warning messages as strings
     *
     * @return array
     */
    public function getWarningMessages(): array
    {
        return array_map(fn($warn) => $warn['message'] ?? '', $this->warnings);
    }
}
