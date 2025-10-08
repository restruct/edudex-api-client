<?php

namespace Restruct\EduDex\Types;

/**
 * Validation message from API
 *
 * Represents a single validation error or warning
 *
 * @package Restruct\EduDex\Types
 */
class ValidationMessage
{
    /**
     * Severity levels
     */
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    /**
     * Message text
     *
     * @var string
     */
    public string $message;

    /**
     * Severity level (error, warning, info)
     *
     * @var string
     */
    public string $severity;

    /**
     * Context path (JSON path to the field with error)
     *
     * @var string|null
     */
    public ?string $contextPath;

    /**
     * Error code
     *
     * @var string|null
     */
    public ?string $code;

    /**
     * Additional context data
     *
     * @var array
     */
    public array $context;

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
        string $message,
        string $severity = self::SEVERITY_ERROR,
        ?string $contextPath = null,
        ?string $code = null,
        array $context = []
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
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            $data['message'] ?? '',
            $data['severity'] ?? self::SEVERITY_ERROR,
            $data['contextPath'] ?? null,
            $data['code'] ?? null,
            $data['context'] ?? []
        );
    }

    /**
     * Check if this is an error
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->severity === self::SEVERITY_ERROR;
    }

    /**
     * Check if this is a warning
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->severity === self::SEVERITY_WARNING;
    }

    /**
     * Check if this is an info message
     *
     * @return bool
     */
    public function isInfo(): bool
    {
        return $this->severity === self::SEVERITY_INFO;
    }

    /**
     * Get formatted message with severity and context
     *
     * @return string
     */
    public function toString(): string
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
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'severity' => $this->severity,
            'contextPath' => $this->contextPath,
            'code' => $this->code,
            'context' => $this->context,
        ];
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
