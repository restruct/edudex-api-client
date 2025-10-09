<?php

/**
 * Base model class for all EduDex API models
 *
 * Provides common functionality for hydration, serialization, and type casting
 */
abstract class EduDexModel implements JsonSerializable
{
    /**
     * Create model instance from API response array
     *
     * @param array $data
     * @return static
     */
    public static function fromArray($data)
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
    protected function fill($data)
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
    protected function castProperty($key, $value)
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
    public function toArray($includeNull = false)
    {
        $result = array();

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
    protected function serializeValue($value)
    {
        if ($value instanceof EduDexModel) {
            return $value->toArray();
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }
        if (is_array($value)) {
            return array_map(array($this, 'serializeValue'), $value);
        }
        return $value;
    }

    /**
     * Convert model to JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Cast value to DateTime
     *
     * @param mixed $value
     * @return DateTime|null
     */
    protected function castToDateTime($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        try {
            return new DateTime($value);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cast value to boolean
     *
     * @param mixed $value
     * @return bool
     */
    protected function castToBool($value)
    {
        return (bool) $value;
    }

    /**
     * Cast value to integer
     *
     * @param mixed $value
     * @return int|null
     */
    protected function castToInt($value)
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * Cast value to float
     *
     * @param mixed $value
     * @return float|null
     */
    protected function castToFloat($value)
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * Cast value to string
     *
     * @param mixed $value
     * @return string|null
     */
    protected function castToString($value)
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
    protected function castToModelArray($items, $modelClass)
    {
        return array_map(function($item) use ($modelClass) {
            return is_array($item) ? call_user_func(array($modelClass, 'fromArray'), $item) : $item;
        }, $items);
    }

    /**
     * Get a property value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->{$key}) ? $this->{$key} : $default;
    }

    /**
     * Check if property exists and is not null
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->{$key});
    }

    /**
     * Magic getter for properties
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->{$name}) ? $this->{$name} : null;
    }

    /**
     * Magic isset for properties
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->{$name});
    }
}
