<?php

namespace Restruct\EduDex\Models;

use DateTime;

/**
 * Webhook model
 *
 * Represents a webhook subscription for catalog changes
 *
 * @package Restruct\EduDex\Models
 */
class Webhook extends Model
{
    /**
     * Webhook ID
     *
     * @var string
     */
    public string $id;

    /**
     * Webhook URL
     *
     * @var string
     */
    public string $url;

    /**
     * Events to listen to
     *
     * @var array<string> e.g., ['catalog', 'program']
     */
    public array $events = [];

    /**
     * Active status
     *
     * @var bool
     */
    public bool $active;

    /**
     * Last called timestamp
     *
     * @var DateTime|null
     */
    public ?DateTime $lastCalled = null;

    /**
     * Last HTTP status code
     *
     * @var int
     */
    public int $lastStatus;

    /**
     * Last result data
     *
     * @var array|null
     */
    public ?array $lastResult = null;

    /**
     * @inheritDoc
     */
    protected function castProperty(string $key, mixed $value): mixed
    {
        return match ($key) {
            'active' => $this->castToBool($value),
            'lastCalled' => $this->castToDateTime($value),
            'lastStatus' => $this->castToInt($value) ?? 0,
            'events' => (array) $value,
            'lastResult' => is_array($value) ? $value : null,
            default => $value,
        };
    }

    /**
     * Check if last call was successful (2xx status)
     *
     * @return bool
     */
    public function wasLastCallSuccessful(): bool
    {
        return $this->lastStatus >= 200 && $this->lastStatus < 300;
    }

    /**
     * Check if webhook has been called
     *
     * @return bool
     */
    public function hasBeenCalled(): bool
    {
        return $this->lastCalled !== null;
    }

    /**
     * Get last error message if available
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        if ($this->wasLastCallSuccessful() || $this->lastResult === null) {
            return null;
        }

        return $this->lastResult['error'] ?? $this->lastResult['message'] ?? null;
    }

    /**
     * Check if webhook listens to specific event
     *
     * @param string $event
     * @return bool
     */
    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events);
    }

    /**
     * Check if webhook listens to catalog events
     *
     * @return bool
     */
    public function listensToCatalog(): bool
    {
        return $this->listensTo('catalog');
    }

    /**
     * Check if webhook listens to program events
     *
     * @return bool
     */
    public function listensToProgram(): bool
    {
        return $this->listensTo('program');
    }

    /**
     * Check if webhook is failing (not successful and has been called)
     *
     * @return bool
     */
    public function isFailing(): bool
    {
        return $this->hasBeenCalled() && !$this->wasLastCallSuccessful();
    }
}
