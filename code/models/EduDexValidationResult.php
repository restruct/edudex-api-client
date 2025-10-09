<?php

/**
 * Validation Result model
 *
 * Represents the result of a validation request
 */
class EduDexValidationResult extends EduDexModel
{
    /**
     * Validation messages
     *
     * @var array
     */
    public $messages = array();

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'messages') {
            return $this->castToValidationMessages($value);
        } else {
            return $value;
        }
    }

    /**
     * Cast array to ValidationMessage objects
     *
     * @param mixed $value
     * @return array
     */
    protected function castToValidationMessages($value)
    {
        if (!is_array($value)) {
            return array();
        }

        $self = $this;
        return array_map(
            function($msg) {
                return is_array($msg) ? ValidationMessage::fromArray($msg) : $msg;
            },
            $value
        );
    }

    /**
     * Check if validation has errors
     *
     * @return bool
     */
    public function hasErrors()
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
    public function hasWarnings()
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
     * @return array
     */
    public function getErrors()
    {
        return array_filter(
            $this->messages,
            function($msg) {
                return $msg->isError();
            }
        );
    }

    /**
     * Get only warning messages
     *
     * @return array
     */
    public function getWarnings()
    {
        return array_filter(
            $this->messages,
            function($msg) {
                return $msg->isWarning();
            }
        );
    }

    /**
     * Get only info messages
     *
     * @return array
     */
    public function getInfo()
    {
        return array_filter(
            $this->messages,
            function($msg) {
                return $msg->isInfo();
            }
        );
    }

    /**
     * Check if validation passed (no errors)
     *
     * @return bool
     */
    public function isValid()
    {
        return !$this->hasErrors();
    }

    /**
     * Get count of errors
     *
     * @return int
     */
    public function getErrorCount()
    {
        return count($this->getErrors());
    }

    /**
     * Get count of warnings
     *
     * @return int
     */
    public function getWarningCount()
    {
        return count($this->getWarnings());
    }

    /**
     * Get all error messages as strings
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return array_map(
            function($msg) {
                return $msg->message;
            },
            $this->getErrors()
        );
    }

    /**
     * Get all warning messages as strings
     *
     * @return array
     */
    public function getWarningMessages()
    {
        return array_map(
            function($msg) {
                return $msg->message;
            },
            $this->getWarnings()
        );
    }
}
