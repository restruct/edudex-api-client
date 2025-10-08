<?php

namespace Restruct\EduDex\Models;

use Restruct\EduDex\Types\LocalizedString;

/**
 * Accreditor model
 *
 * Represents an accreditor organization
 *
 * @package Restruct\EduDex\Models
 */
class Accreditor extends Model
{
    /**
     * Accreditor ID (orgUnitId)
     *
     * @var string
     */
    public string $id;

    /**
     * Localized accreditor name
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
