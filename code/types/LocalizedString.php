<?php

/**
 * Localized string handler for multi-language content
 *
 * Handles the localizedSet200 type from the API
 */
class LocalizedString implements JsonSerializable
{
    /**
     * Localized values keyed by language code
     *
     * @var array
     */
    protected $values = [];

    /**
     * Default locale to use when no specific locale is requested
     *
     * @var string
     */
    protected $defaultLocale = 'nl';

    /**
     * Fallback locale chain
     *
     * @var array
     */
    protected $fallbackLocales = ['nl', 'en'];

    /**
     * Constructor
     *
     * @param array $values Localized values
     * @param string $defaultLocale Default locale
     */
    public function __construct($values = array(), $defaultLocale = 'nl')
    {
        $this->values = $values;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Create instance from array
     *
     * @param array $values
     * @param string $defaultLocale
     * @return LocalizedString
     */
    public static function fromArray($values, $defaultLocale = 'nl')
    {
        return new self($values, $defaultLocale);
    }

    /**
     * Get localized value for specific locale
     *
     * @param string|null $locale Locale code (e.g., 'nl', 'en')
     * @return string|null
     */
    public function get($locale = null)
    {
        $locale = $locale !== null ? $locale : $this->defaultLocale;

        // Try requested locale
        if (isset($this->values[$locale])) {
            return $this->values[$locale];
        }

        // Try fallback locales
        foreach ($this->fallbackLocales as $fallback) {
            if (isset($this->values[$fallback])) {
                return $this->values[$fallback];
            }
        }

        // Return first available value
        return !empty($this->values) ? reset($this->values) : null;
    }

    /**
     * Set localized value for specific locale
     *
     * @param string $locale
     * @param string $value
     * @return LocalizedString
     */
    public function set($locale, $value)
    {
        $this->values[$locale] = $value;
        return $this;
    }

    /**
     * Check if locale exists
     *
     * @param string $locale
     * @return bool
     */
    public function has($locale)
    {
        return isset($this->values[$locale]);
    }

    /**
     * Get all localized values
     *
     * @return array
     */
    public function all()
    {
        return $this->values;
    }

    /**
     * Get available locales
     *
     * @return array
     */
    public function getLocales()
    {
        return array_keys($this->values);
    }

    /**
     * Check if empty (no values)
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->values);
    }

    /**
     * Set fallback locale chain
     *
     * @param array $locales
     * @return LocalizedString
     */
    public function setFallbackLocales($locales)
    {
        $this->fallbackLocales = $locales;
        return $this;
    }

    /**
     * Set default locale
     *
     * @param string $locale
     * @return LocalizedString
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
        return $this;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }

    /**
     * JSON serialization
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->values;
    }

    /**
     * String representation (uses default locale)
     *
     * @return string
     */
    public function __toString()
    {
        $value = $this->get();
        return $value !== null ? $value : '';
    }
}
