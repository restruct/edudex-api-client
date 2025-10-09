<?php

/**
 * Dynamic Catalog model
 *
 * Represents a dynamic catalog with automatic supplier filters
 */
class DynamicCatalog extends EduDexModel
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
     * Region filter (postal code ranges)
     * Format: "1000-1999,2345" or null
     *
     * @var string|null
     */
    public $regionFilter = null;

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
        } elseif ($key === 'regionFilter') {
            return $this->castToString($value);
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
     * Check if catalog has region filter
     *
     * @return bool
     */
    public function hasRegionFilter()
    {
        return !empty($this->regionFilter);
    }

    /**
     * Get postal code ranges from region filter
     *
     * @return array Array of ranges, e.g., [['1000', '1999'], ['2345', '2345']]
     */
    public function getPostalCodeRanges()
    {
        if (empty($this->regionFilter)) {
            return array();
        }

        $ranges = array();
        $parts = explode(',', $this->regionFilter);

        foreach ($parts as $part) {
            if (strpos($part, '-') !== false) {
                $splitParts = explode('-', $part, 2);
                $start = $splitParts[0];
                $end = $splitParts[1];
                $ranges[] = array('start' => trim($start), 'end' => trim($end));
            } else {
                $code = trim($part);
                $ranges[] = array('start' => $code, 'end' => $code);
            }
        }

        return $ranges;
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
