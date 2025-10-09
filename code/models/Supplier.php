<?php

/**
 * Supplier model
 *
 * Represents a supplier organization
 */
class Supplier extends EduDexModel
{
    /**
     * Supplier ID (orgUnitId)
     *
     * @var string
     */
    public $id;

    /**
     * Localized supplier name
     *
     * @var LocalizedString
     */
    public $name;

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'name') {
            return is_array($value) ? LocalizedString::fromArray($value) : $value;
        } else {
            return $value;
        }
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
}
