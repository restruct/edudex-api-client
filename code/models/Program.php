<?php

/**
 * Program model
 *
 * Represents a training program/course
 */
class Program extends EduDexModel
{
    /**
     * Organization unit ID (supplier ID)
     *
     * @var string
     */
    public $orgUnitId;

    /**
     * Program ID
     *
     * @var string
     */
    public $programId;

    /**
     * Client ID
     *
     * @var string
     */
    public $clientId;

    /**
     * Editor name
     *
     * @var string|null
     */
    public $editor = null;

    /**
     * Format version
     *
     * @var string
     */
    public $format;

    /**
     * Generator (CMS/system that created the data)
     *
     * @var string|null
     */
    public $generator = null;

    /**
     * Last edited timestamp
     *
     * @var DateTime|null
     */
    public $lastEdited = null;

    /**
     * Program data (full program structure)
     *
     * @var array
     */
    public $programData = array();

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'lastEdited') {
            return $this->castToDateTime($value);
        } elseif ($key === 'programData') {
            return is_array($value) ? $value : array();
        } else {
            return $value;
        }
    }

    /**
     * Get program title (from programData)
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getTitle($locale = 'nl')
    {
        $titles = isset($this->programData['programDescriptions']['title']) ? $this->programData['programDescriptions']['title'] : null;

        if (is_array($titles)) {
            return isset($titles[$locale]) ? $titles[$locale] : (isset($titles['nl']) ? $titles['nl'] : (isset($titles['en']) ? $titles['en'] : null));
        }

        return null;
    }

    /**
     * Get program description (from programData)
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getDescription($locale = 'nl')
    {
        $descriptions = isset($this->programData['programDescriptions']['description']) ? $this->programData['programDescriptions']['description'] : null;

        if (is_array($descriptions)) {
            return isset($descriptions[$locale]) ? $descriptions[$locale] : (isset($descriptions['nl']) ? $descriptions['nl'] : (isset($descriptions['en']) ? $descriptions['en'] : null));
        }

        return null;
    }

    /**
     * Check if program has been edited
     *
     * @return bool
     */
    public function hasBeenEdited()
    {
        return $this->lastEdited !== null;
    }
}
