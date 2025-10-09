<?php

/**
 * Static Catalog model
 *
 * Represents a static catalog with manually added programs
 */
class StaticCatalog extends EduDexModel
{
    /**
     * Catalog ID (UUID)
     *
     * @var string
     */
    public $catalogId;

    /**
     * Client ID
     *
     * @var string
     */
    public $clientId;

    /**
     * Catalog title
     *
     * @var string
     */
    public $title;

    /**
     * Number of active programs (programs that still exist)
     *
     * @var int
     */
    public $countActive;

    /**
     * Total number of program references (including removed programs)
     *
     * @var int
     */
    public $countTotal;

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'countActive' || $key === 'countTotal') {
            return $this->castToInt($value);
        } else {
            return $value;
        }
    }

    /**
     * Check if catalog has inactive/removed programs
     *
     * @return bool
     */
    public function hasInactivePrograms()
    {
        return $this->countTotal > $this->countActive;
    }

    /**
     * Get count of inactive programs
     *
     * @return int
     */
    public function getInactiveCount()
    {
        return $this->countTotal - $this->countActive;
    }

    /**
     * Check if catalog is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->countTotal === 0;
    }

    /**
     * Get percentage of active programs
     *
     * @return float
     */
    public function getActivePercentage()
    {
        if ($this->countTotal === 0) {
            return 0.0;
        }

        return ($this->countActive / $this->countTotal) * 100;
    }
}
