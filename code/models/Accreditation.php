<?php

/**
 * Accreditation model
 *
 * Represents an accreditation for a supplier
 */
class Accreditation extends EduDexModel
{
    /**
     * Accreditation ID (UUID)
     *
     * @var string
     */
    public $accreditationId;

    /**
     * Organization unit ID (supplier ID)
     *
     * @var string
     */
    public $orgUnitId;

    /**
     * Accreditation type (e.g., 'CRKBO', 'ISO')
     *
     * @var string
     */
    public $accreditation;

    /**
     * Valid from date
     *
     * @var DateTime
     */
    public $validFrom;

    /**
     * Valid until date
     *
     * @var DateTime
     */
    public $validUntil;

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'validFrom' || $key === 'validUntil') {
            return $this->castToDateTime($value);
        } else {
            return $value;
        }
    }

    /**
     * Check if accreditation is currently valid
     *
     * @return bool
     */
    public function isValid()
    {
        $now = new DateTime();
        return $this->validFrom <= $now && $this->validUntil >= $now;
    }

    /**
     * Check if accreditation has expired
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->validUntil < new DateTime();
    }

    /**
     * Check if accreditation is not yet valid
     *
     * @return bool
     */
    public function isFuture()
    {
        return $this->validFrom > new DateTime();
    }

    /**
     * Get days until expiry
     *
     * @return int Negative if expired
     */
    public function daysUntilExpiry()
    {
        $now = new DateTime();
        return $now->diff($this->validUntil)->days * ($this->isExpired() ? -1 : 1);
    }
}
