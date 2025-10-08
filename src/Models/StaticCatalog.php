<?php

namespace Restruct\EduDex\Models;

/**
 * Static Catalog model
 *
 * Represents a static catalog with manually added programs
 *
 * @package Restruct\EduDex\Models
 */
class StaticCatalog extends Model
{
    /**
     * Catalog ID (UUID)
     *
     * @var string
     */
    public string $catalogId;

    /**
     * Client ID
     *
     * @var string
     */
    public string $clientId;

    /**
     * Catalog title
     *
     * @var string
     */
    public string $title;

    /**
     * Number of active programs (programs that still exist)
     *
     * @var int
     */
    public int $countActive;

    /**
     * Total number of program references (including removed programs)
     *
     * @var int
     */
    public int $countTotal;

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'countActive', 'countTotal' => $this->castToInt($value),
            default => $value,
        };
    }

    /**
     * Check if catalog has inactive/removed programs
     *
     * @return bool
     */
    public function hasInactivePrograms(): bool
    {
        return $this->countTotal > $this->countActive;
    }

    /**
     * Get count of inactive programs
     *
     * @return int
     */
    public function getInactiveCount(): int
    {
        return $this->countTotal - $this->countActive;
    }

    /**
     * Check if catalog is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->countTotal === 0;
    }

    /**
     * Get percentage of active programs
     *
     * @return float
     */
    public function getActivePercentage(): float
    {
        if ($this->countTotal === 0) {
            return 0.0;
        }

        return ($this->countActive / $this->countTotal) * 100;
    }
}
