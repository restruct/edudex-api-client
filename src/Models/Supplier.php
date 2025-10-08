<?php

namespace Restruct\EduDex\Models;

use Restruct\EduDex\Types\LocalizedString;

/**
 * Supplier model
 *
 * Represents a supplier organization
 *
 * @package Restruct\EduDex\Models
 */
class Supplier extends Model
{
    /**
     * Supplier ID (orgUnitId)
     *
     * @var string
     */
    public string $id;

    /**
     * Localized supplier name
     *
     * @var LocalizedString
     */
    public LocalizedString $name;

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'name' => is_array($value) ? LocalizedString::fromArray($value) : $value,
            default => $value,
        };
    }

    /**
     * Get localized name for specific locale
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getLocalizedName(?string $locale = null): ?string
    {
        return $this->name->get($locale);
    }
}
