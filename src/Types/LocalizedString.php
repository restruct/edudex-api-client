<?php

namespace Restruct\EduDex\Types;

use JsonSerializable;

/**
 * Localized string handler for multi-language content
 *
 * Handles the localizedSet200 type from the API
 *
 * @package Restruct\EduDex\Types
 */
class LocalizedString implements JsonSerializable
{
    /**
     * Localized values keyed by language code
     *
     * @var array<string, string>
     */
    protected array $values = [];

    /**
     * Default locale to use when no specific locale is requested
     *
     * @var string
     */
    protected string $defaultLocale = 'nl';

    /**
     * Fallback locale chain
     *
     * @var array
     */
    protected array $fallbackLocales = ['nl', 'en'];

    /**
     * Constructor
     *
     * @param array $values Localized values
     * @param string $defaultLocale Default locale
     */
    public function __construct(array $values = [], string $defaultLocale = 'nl')
    {
        $this->values = $values;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Create instance from array
     *
     * @param array $values
     * @param string $defaultLocale
     * @return static
     */
    public static function fromArray(array $values, string $defaultLocale = 'nl'): static
    {
        return new static($values, $defaultLocale);
    }

    /**
     * Get localized value for specific locale
     *
     * @param string|null $locale Locale code (e.g., 'nl', 'en')
     * @return string|null
     */
    public function get(?string $locale = null): ?string
    {
        $locale = $locale ?? $this->defaultLocale;

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
     * @return static
     */
    public function set(string $locale, string $value): static
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
    public function has(string $locale): bool
    {
        return isset($this->values[$locale]);
    }

    /**
     * Get all localized values
     *
     * @return array
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Get available locales
     *
     * @return array
     */
    public function getLocales(): array
    {
        return array_keys($this->values);
    }

    /**
     * Check if empty (no values)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Set fallback locale chain
     *
     * @param array $locales
     * @return static
     */
    public function setFallbackLocales(array $locales): static
    {
        $this->fallbackLocales = $locales;
        return $this;
    }

    /**
     * Set default locale
     *
     * @param string $locale
     * @return static
     */
    public function setDefaultLocale(string $locale): static
    {
        $this->defaultLocale = $locale;
        return $this;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * JSON serialization
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->values;
    }

    /**
     * String representation (uses default locale)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->get() ?? '';
    }
}
