<?php

namespace Restruct\EduDex\Models;

use Restruct\EduDex\Types\LocalizedString;

/**
 * Organization model
 *
 * Represents an organization in the EDU-DEX system
 *
 * @package Restruct\EduDex\Models
 */
class Organization extends Model
{
    /**
     * Organization ID
     *
     * @var string
     */
    public string $id;

    /**
     * Localized organization name
     *
     * @var LocalizedString
     */
    public LocalizedString $name;

    /**
     * Organization roles
     *
     * @var array<string> Array of 'supplier', 'client', 'intermediary', 'accreditor'
     */
    public array $roles = [];

    /**
     * VAT exempt status
     *
     * @var bool
     */
    public bool $vatExempt = false;

    /**
     * Accreditations
     *
     * @var array<string>
     */
    public array $accreditations = [];

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'name' => is_array($value) ? LocalizedString::fromArray($value) : $value,
            'vatExempt' => $this->castToBool($value),
            'roles', 'accreditations' => (array) $value,
            default => $value,
        };
    }

    /**
     * Check if organization is a supplier
     *
     * @return bool
     */
    public function isSupplier(): bool
    {
        return in_array('supplier', $this->roles);
    }

    /**
     * Check if organization is a client
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return in_array('client', $this->roles);
    }

    /**
     * Check if organization is an intermediary
     *
     * @return bool
     */
    public function isIntermediary(): bool
    {
        return in_array('intermediary', $this->roles);
    }

    /**
     * Check if organization is an accreditor
     *
     * @return bool
     */
    public function isAccreditor(): bool
    {
        return in_array('accreditor', $this->roles);
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

    /**
     * Check if organization has specific accreditation
     *
     * @param string $accreditation
     * @return bool
     */
    public function hasAccreditation(string $accreditation): bool
    {
        return in_array($accreditation, $this->accreditations);
    }
}
