<?php

/**
 * Accreditor model
 *
 * Represents an accreditor organization
 */
class Accreditor extends EduDexModel
{
    /**
     * Accreditor ID (orgUnitId)
     *
     * @var string
     */
    public $id;

    /**
     * Localized accreditor name
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
