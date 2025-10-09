<?php

/**
 * Validation message from API
 *
 * Represents a single validation error or warning
 */
class ValidationMessage
{
    /**
     * Severity levels
     */
    const SEVERITY_ERROR = 'error';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_INFO = 'info';

    /**
     * Message text
     *
     * @var string
     */
    public $message;

    /**
     * Severity level (error, warning, info)
     *
     * @var string
     */
    public $severity;

    /**
     * Context path (JSON path to the field with error)
     *
     * @var string|null
     */
    public $contextPath;

    /**
     * Error code
     *
     * @var string|null
     */
    public $code;

    /**
     * Additional context data
     *
     * @var array
     */
    public $context;

    /**
     * Constructor
     *
     * @param string $message
     * @param string $severity
     * @param string|null $contextPath
     * @param string|null $code
     * @param array $context
     */
    public function __construct(
        $message,
        $severity = self::SEVERITY_ERROR,
        $contextPath = null,
        $code = null,
        $context = array()
    ) {
        $this->message = $message;
        $this->severity = $severity;
        $this->contextPath = $contextPath;
        $this->code = $code;
        $this->context = $context;
    }

    /**
     * Create from API response array
     *
     * @param array $data
     * @return ValidationMessage
     */
    public static function fromArray($data)
    {
        return new self(
            isset($data['message']) ? $data['message'] : '',
            isset($data['severity']) ? $data['severity'] : self::SEVERITY_ERROR,
            isset($data['contextPath']) ? $data['contextPath'] : null,
            isset($data['code']) ? $data['code'] : null,
            isset($data['context']) ? $data['context'] : array()
        );
    }

    /**
     * Check if this is an error
     *
     * @return bool
     */
    public function isError()
    {
        return $this->severity === self::SEVERITY_ERROR;
    }

    /**
     * Check if this is a warning
     *
     * @return bool
     */
    public function isWarning()
    {
        return $this->severity === self::SEVERITY_WARNING;
    }

    /**
     * Check if this is an info message
     *
     * @return bool
     */
    public function isInfo()
    {
        return $this->severity === self::SEVERITY_INFO;
    }

    /**
     * Get formatted message with severity and context
     *
     * @return string
     */
    public function toString()
    {
        $prefix = strtoupper($this->severity);
        $suffix = $this->contextPath ? " (at {$this->contextPath})" : '';

        return "[{$prefix}] {$this->message}{$suffix}";
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'message' => $this->message,
            'severity' => $this->severity,
            'contextPath' => $this->contextPath,
            'code' => $this->code,
            'context' => $this->context,
        );
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
