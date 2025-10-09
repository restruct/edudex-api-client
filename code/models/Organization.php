<?php

/**
 * Organization model
 *
 * Represents an organization in the EDU-DEX system
 */
class Organization extends EduDexModel
{
    /**
     * Organization ID
     *
     * @var string
     */
    public $id;

    /**
     * Localized organization name
     *
     * @var LocalizedString
     */
    public $name;

    /**
     * Organization roles
     *
     * @var array Array of 'supplier', 'client', 'intermediary', 'accreditor'
     */
    public $roles = array();

    /**
     * VAT exempt status
     *
     * @var bool
     */
    public $vatExempt = false;

    /**
     * Accreditations
     *
     * @var array
     */
    public $accreditations = array();

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'name') {
            return is_array($value) ? LocalizedString::fromArray($value) : $value;
        } elseif ($key === 'vatExempt') {
            return $this->castToBool($value);
        } elseif ($key === 'roles' || $key === 'accreditations') {
            return (array) $value;
        } else {
            return $value;
        }
    }

    /**
     * Check if organization is a supplier
     *
     * @return bool
     */
    public function isSupplier()
    {
        return in_array('supplier', $this->roles);
    }

    /**
     * Check if organization is a client
     *
     * @return bool
     */
    public function isClient()
    {
        return in_array('client', $this->roles);
    }

    /**
     * Check if organization is an intermediary
     *
     * @return bool
     */
    public function isIntermediary()
    {
        return in_array('intermediary', $this->roles);
    }

    /**
     * Check if organization is an accreditor
     *
     * @return bool
     */
    public function isAccreditor()
    {
        return in_array('accreditor', $this->roles);
    }

    /**
     * Get localized name for specific locale
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getLocalizedName($locale = null)
    {
        return $this->name->get($locale);
    }

    /**
     * Check if organization has specific accreditation
     *
     * @param string $accreditation
     * @return bool
     */
    public function hasAccreditation($accreditation)
    {
        return in_array($accreditation, $this->accreditations);
    }
}
