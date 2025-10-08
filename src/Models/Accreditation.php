<?php

namespace Restruct\EduDex\Models;

use DateTime;

/**
 * Accreditation model
 *
 * Represents an accreditation for a supplier
 *
 * @package Restruct\EduDex\Models
 */
class Accreditation extends Model
{
    /**
     * Accreditation ID (UUID)
     *
     * @var string
     */
    public string $accreditationId;

    /**
     * Organization unit ID (supplier ID)
     *
     * @var string
     */
    public string $orgUnitId;

    /**
     * Accreditation type (e.g., 'CRKBO', 'ISO')
     *
     * @var string
     */
    public string $accreditation;

    /**
     * Valid from date
     *
     * @var DateTime
     */
    public DateTime $validFrom;

    /**
     * Valid until date
     *
     * @var DateTime
     */
    public DateTime $validUntil;

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'validFrom', 'validUntil' => $this->castToDateTime($value),
            default => $value,
        };
    }

    /**
     * Check if accreditation is currently valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $now = new DateTime();
        return $this->validFrom <= $now && $this->validUntil >= $now;
    }

    /**
     * Check if accreditation has expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->validUntil < new DateTime();
    }

    /**
     * Check if accreditation is not yet valid
     *
     * @return bool
     */
    public function isFuture(): bool
    {
        return $this->validFrom > new DateTime();
    }

    /**
     * Get days until expiry
     *
     * @return int Negative if expired
     */
    public function daysUntilExpiry(): int
    {
        $now = new DateTime();
        return $now->diff($this->validUntil)->days * ($this->isExpired() ? -1 : 1);
    }
}
