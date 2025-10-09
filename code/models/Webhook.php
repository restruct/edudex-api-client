<?php

/**
 * Webhook model
 *
 * Represents a webhook subscription for catalog changes
 */
class Webhook extends EduDexModel
{
    /**
     * Webhook ID
     *
     * @var string
     */
    public $id;

    /**
     * Webhook URL
     *
     * @var string
     */
    public $url;

    /**
     * Events to listen to
     *
     * @var array e.g., ['catalog', 'program']
     */
    public $events = array();

    /**
     * Active status
     *
     * @var bool
     */
    public $active;

    /**
     * Last called timestamp
     *
     * @var DateTime|null
     */
    public $lastCalled = null;

    /**
     * Last HTTP status code
     *
     * @var int
     */
    public $lastStatus;

    /**
     * Last result data
     *
     * @var array|null
     */
    public $lastResult = null;

    /**
     * @inheritDoc
     */
    protected function castProperty($key, $value)
    {
        if ($key === 'active') {
            return $this->castToBool($value);
        } elseif ($key === 'lastCalled') {
            return $this->castToDateTime($value);
        } elseif ($key === 'lastStatus') {
            $casted = $this->castToInt($value);
            return $casted !== null ? $casted : 0;
        } elseif ($key === 'events') {
            return (array) $value;
        } elseif ($key === 'lastResult') {
            return is_array($value) ? $value : null;
        } else {
            return $value;
        }
    }

    /**
     * Check if last call was successful (2xx status)
     *
     * @return bool
     */
    public function wasLastCallSuccessful()
    {
        return $this->lastStatus >= 200 && $this->lastStatus < 300;
    }

    /**
     * Check if webhook has been called
     *
     * @return bool
     */
    public function hasBeenCalled()
    {
        return $this->lastCalled !== null;
    }

    /**
     * Get last error message if available
     *
     * @return string|null
     */
    public function getLastError()
    {
        if ($this->wasLastCallSuccessful() || $this->lastResult === null) {
            return null;
        }

        return isset($this->lastResult['error']) ? $this->lastResult['error'] : (isset($this->lastResult['message']) ? $this->lastResult['message'] : null);
    }

    /**
     * Check if webhook listens to specific event
     *
     * @param string $event
     * @return bool
     */
    public function listensTo($event)
    {
        return in_array($event, $this->events);
    }

    /**
     * Check if webhook listens to catalog events
     *
     * @return bool
     */
    public function listensToCatalog()
    {
        return $this->listensTo('catalog');
    }

    /**
     * Check if webhook listens to program events
     *
     * @return bool
     */
    public function listensToProgram()
    {
        return $this->listensTo('program');
    }

    /**
     * Check if webhook is failing (not successful and has been called)
     *
     * @return bool
     */
    public function isFailing()
    {
        return $this->hasBeenCalled() && !$this->wasLastCallSuccessful();
    }
}
