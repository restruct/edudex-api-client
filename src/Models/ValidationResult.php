<?php

namespace Restruct\EduDex\Models;

use Restruct\EduDex\Types\ValidationMessage;

/**
 * Validation Result model
 *
 * Represents the result of a validation request
 *
 * @package Restruct\EduDex\Models
 */
class ValidationResult extends Model
{
    /**
     * Validation messages
     *
     * @var array<ValidationMessage>
     */
    public array $messages = [];

    /**
     * The API's verdict ("validates" in the spec's validations_response), null if not sent
     *
     * @var bool|null
     */
    public ?bool $validates = null;

    /**
     * @inheritDoc
     */
    protected function fill(array $data): void
    {
        # The spec sends the messages as "validationMessages"; reading only "messages"
        # left them all out, so every result came out valid (#3)
        if (!isset($data['messages']) && isset($data['validationMessages'])) {
            $data['messages'] = $data['validationMessages'];
        }

        parent::fill($data);
    }

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'messages' => $this->castToValidationMessages($value),
            'validates' => $value === null ? null : $this->castToBool($value),
            default => $value,
        };
    }

    /**
     * Cast array to ValidationMessage objects
     *
     * @param mixed $value
     * @return array
     */
    protected function castToValidationMessages(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map(
            fn($msg) => is_array($msg) ? ValidationMessage::fromArray($msg) : $msg,
            $value
        );
    }

    /**
     * Check if validation has errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        foreach ($this->messages as $message) {
            if ($message->isError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if validation has warnings
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        foreach ($this->messages as $message) {
            if ($message->isWarning()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get only error messages
     *
     * @return array<ValidationMessage>
     */
    public function getErrors(): array
    {
        # array_values(): a list, not the positions the messages had in the full set
        return array_values(array_filter(
            $this->messages,
            fn($msg) => $msg->isError()
        ));
    }

    /**
     * Get only warning messages
     *
     * @return array<ValidationMessage>
     */
    public function getWarnings(): array
    {
        # array_values(): a list, not the positions the messages had in the full set
        return array_values(array_filter(
            $this->messages,
            fn($msg) => $msg->isWarning()
        ));
    }

    /**
     * Get only info messages
     *
     * @return array<ValidationMessage>
     */
    public function getInfo(): array
    {
        # array_values(): a list, not the positions the messages had in the full set
        return array_values(array_filter(
            $this->messages,
            fn($msg) => $msg->isInfo()
        ));
    }

    /**
     * Check if validation passed (no errors)
     *
     * @return bool
     */
    public function isValid(): bool
    {
        # An explicit "validates: false" from the API wins, even without error messages
        if ($this->validates === false) {
            return false;
        }

        return !$this->hasErrors();
    }

    /**
     * Get count of errors
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return count($this->getErrors());
    }

    /**
     * Get count of warnings
     *
     * @return int
     */
    public function getWarningCount(): int
    {
        return count($this->getWarnings());
    }

    /**
     * Get all error messages as strings
     *
     * @return array<string>
     */
    public function getErrorMessages(): array
    {
        return array_map(
            fn($msg) => $msg->message,
            $this->getErrors()
        );
    }

    /**
     * Get all warning messages as strings
     *
     * @return array<string>
     */
    public function getWarningMessages(): array
    {
        return array_map(
            fn($msg) => $msg->message,
            $this->getWarnings()
        );
    }
}
