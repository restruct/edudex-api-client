<?php

namespace Restruct\EduDex\Models;

/**
 * Dynamic Catalog model
 *
 * Represents a dynamic catalog with automatic supplier filters
 *
 * @package Restruct\EduDex\Models
 */
class DynamicCatalog extends Model
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
     * Region filter (postal code ranges)
     * Format: "1000-1999,2345" or null
     *
     * @var string|null
     */
    public ?string $regionFilter = null;

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
            'regionFilter' => $this->castToString($value),
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
     * Check if catalog has region filter
     *
     * @return bool
     */
    public function hasRegionFilter(): bool
    {
        return !empty($this->regionFilter);
    }

    /**
     * Get postal code ranges from region filter
     *
     * @return array Array of ranges, e.g., [['1000', '1999'], ['2345', '2345']]
     */
    public function getPostalCodeRanges(): array
    {
        if (empty($this->regionFilter)) {
            return [];
        }

        $ranges = [];
        $parts = explode(',', $this->regionFilter);

        foreach ($parts as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part, 2);
                $ranges[] = ['start' => trim($start), 'end' => trim($end)];
            } else {
                $code = trim($part);
                $ranges[] = ['start' => $code, 'end' => $code];
            }
        }

        return $ranges;
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
