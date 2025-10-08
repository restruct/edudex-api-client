<?php

namespace Restruct\EduDex\Models;

use DateTime;

/**
 * Program model
 *
 * Represents a training program/course
 *
 * @package Restruct\EduDex\Models
 */
class Program extends Model
{
    /**
     * Organization unit ID (supplier ID)
     *
     * @var string
     */
    public string $orgUnitId;

    /**
     * Program ID
     *
     * @var string
     */
    public string $programId;

    /**
     * Client ID
     *
     * @var string
     */
    public string $clientId;

    /**
     * Editor name
     *
     * @var string|null
     */
    public ?string $editor = null;

    /**
     * Format version
     *
     * @var string
     */
    public string $format;

    /**
     * Generator (CMS/system that created the data)
     *
     * @var string|null
     */
    public ?string $generator = null;

    /**
     * Last edited timestamp
     *
     * @var DateTime|null
     */
    public ?DateTime $lastEdited = null;

    /**
     * Program data (full program structure)
     *
     * @var array
     */
    public array $programData = [];

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'lastEdited' => $this->castToDateTime($value),
            'programData' => is_array($value) ? $value : [],
            default => $value,
        };
    }

    /**
     * Get program title (from programData)
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getTitle(?string $locale = 'nl'): ?string
    {
        $titles = $this->programData['programDescriptions']['title'] ?? null;

        if (is_array($titles)) {
            return $titles[$locale] ?? $titles['nl'] ?? $titles['en'] ?? null;
        }

        return null;
    }

    /**
     * Get program description (from programData)
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getDescription(?string $locale = 'nl'): ?string
    {
        $descriptions = $this->programData['programDescriptions']['description'] ?? null;

        if (is_array($descriptions)) {
            return $descriptions[$locale] ?? $descriptions['nl'] ?? $descriptions['en'] ?? null;
        }

        return null;
    }

    /**
     * Check if program has been edited
     *
     * @return bool
     */
    public function hasBeenEdited(): bool
    {
        return $this->lastEdited !== null;
    }
}
