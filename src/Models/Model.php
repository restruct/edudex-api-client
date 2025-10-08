<?php

namespace Restruct\EduDex\Models;

use DateTime;
use DateTimeInterface;
use JsonSerializable;

/**
 * Base model class for all EduDex API models
 *
 * Provides common functionality for hydration, serialization, and type casting
 *
 * @package Restruct\EduDex\Models
 */
abstract class Model implements JsonSerializable
{
    /**
     * Create model instance from API response array
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $model = new static();
        $model->fill($data);
        return $model;
    }

    /**
     * Fill model properties from array
     *
     * @param array $data
     * @return void
     */
    protected function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $this->castProperty($key, $value);
            }
        }
    }

    /**
     * Cast property value to appropriate type
     *
     * Override this method in child classes for custom casting
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        // Default implementation - no casting
        // Child classes can override for specific casting rules
        return $value;
    }

    /**
     * Convert model to array
     *
     * @param bool $includeNull Include null values in output
     * @return array
     */
    public function toArray(bool $includeNull = false): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if (!$includeNull && $value === null) {
                continue;
            }

            $result[$key] = $this->serializeValue($value);
        }

        return $result;
    }

    /**
     * Serialize a value for array/JSON output
     *
     * @param mixed $value
     * @return mixed
     */
    protected function serializeValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Model => $value->toArray(),
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_array($value) => array_map([$this, 'serializeValue'], $value),
            default => $value,
        };
    }

    /**
     * Convert model to JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Cast value to DateTime
     *
     * @param mixed $value
     * @return DateTime|null
     */
    protected function castToDateTime(mixed $value): ?DateTime
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        try {
            return new DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Cast value to boolean
     *
     * @param mixed $value
     * @return bool
     */
    protected function castToBool(mixed $value): bool
    {
        return (bool) $value;
    }

    /**
     * Cast value to integer
     *
     * @param mixed $value
     * @return int|null
     */
    protected function castToInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * Cast value to float
     *
     * @param mixed $value
     * @return float|null
     */
    protected function castToFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * Cast value to string
     *
     * @param mixed $value
     * @return string|null
     */
    protected function castToString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * Cast array of data to array of models
     *
     * @param array $items
     * @param string $modelClass
     * @return array
     */
    protected function castToModelArray(array $items, string $modelClass): array
    {
        return array_map(
            fn($item) => is_array($item) ? $modelClass::fromArray($item) : $item,
            $items
        );
    }

    /**
     * Get a property value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->{$key} ?? $default;
    }

    /**
     * Check if property exists and is not null
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->{$key});
    }

    /**
     * Magic getter for properties
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->{$name} ?? null;
    }

    /**
     * Magic isset for properties
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->{$name});
    }
}
