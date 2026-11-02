<?php

namespace Restruct\EduDex\Endpoints;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Restruct\EduDex\Models\DynamicCatalog;
use Restruct\EduDex\Models\Organization;
use Restruct\EduDex\Models\StaticCatalog;
use Restruct\EduDex\Models\Webhook;

/**
 * Organizations endpoint
 *
 * Manages organizations, catalogs, and webhooks
 *
 * @package Restruct\EduDex\Endpoints
 */
class Organizations extends BaseEndpoint
{
    /**
     * List all organizations
     *
     * @return array<Organization>
     */
    public function list(): array
    {
        $response = $this->sendGet('organizations');
        $items = $this->extractListItems($response, 'organizations');

        return $this->hydrateModels(Organization::class, $items);
    }

    /**
     * Get a single organization
     *
     * @param string $orgUnitId
     * @return Organization
     */
    public function get(string $orgUnitId): Organization
    {
        $response = $this->sendGet("organizations/{$orgUnitId}");
        return $this->hydrateModel(Organization::class, $response);
    }

    /**
     * List all catalogs for an organization: the static ones, then the dynamic ones
     *
     * @deprecated 1.3.0 The spec has no /organizations/{orgUnitId}/catalogs; this now makes two
     *             requests. Use listStaticCatalogs() and listDynamicCatalogs().
     * @param string $orgUnitId
     * @return array Raw catalog arrays
     */
    public function listCatalogs(string $orgUnitId): array
    {
        // $response = $this->sendGet("organizations/{$orgUnitId}/catalogs");
        // return $this->extractListItems($response, 'catalogs');
        # The path above is not in the spec (#3); combine the two lists it does define
        return array_merge(
            $this->extractListItems($this->sendGet("organizations/{$orgUnitId}/staticcatalogs"), 'catalogs'),
            $this->extractListItems($this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs"), 'catalogs')
        );
    }

    /**
     * List static catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array<StaticCatalog>
     */
    public function listStaticCatalogs(string $orgUnitId): array
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/staticcatalogs");
        $items = $this->extractListItems($response, 'catalogs');

        return $this->hydrateModels(StaticCatalog::class, $items);
    }

    /**
     * Get a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return StaticCatalog
     */
    public function getStaticCatalog(string $orgUnitId, string $catalogId): StaticCatalog
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}");
        return $this->hydrateModel(StaticCatalog::class, $response);
    }

    /**
     * Create a static catalog
     *
     * @param string $orgUnitId
     * @param string $title
     * @param string $clientId
     * @return StaticCatalog
     */
    public function createStaticCatalog(string $orgUnitId, string $title, string $clientId): StaticCatalog
    {
        $this->validateRequired(compact('orgUnitId', 'title', 'clientId'), ['orgUnitId', 'title', 'clientId']);

        $response = $this->sendPost("organizations/{$orgUnitId}/staticcatalogs", [
            'title' => $title,
            'clientId' => $clientId,
        ]);

        return $this->hydrateModel(StaticCatalog::class, $response);
    }

    /**
     * Update a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $title
     * @return StaticCatalog
     */
    public function updateStaticCatalog(string $orgUnitId, string $catalogId, string $title): StaticCatalog
    {
        $this->validateRequired(compact('orgUnitId', 'catalogId', 'title'), ['orgUnitId', 'catalogId', 'title']);

        $response = $this->sendPatch("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}", [
            'title' => $title,
        ]);

        return $this->hydrateModel(StaticCatalog::class, $response);
    }

    /**
     * Delete a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return void
     */
    public function deleteStaticCatalog(string $orgUnitId, string $catalogId): void
    {
        $this->sendDelete("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}");
    }

    /**
     * Bulk add programs to static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param array $programs Array of ['supplierId' => string, 'programId' => string] (max 1000)
     * @return array Response with an "added" list
     */
    public function bulkAddPrograms(string $orgUnitId, string $catalogId, array $programs): array
    {
        return $this->sendPost(
            "organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs/bulkadd",
            $this->catalogProgramRefs($programs)
        );
    }

    /**
     * Bulk remove programs from static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param array $programs Array of ['supplierId' => string, 'programId' => string] (max 1000)
     * @return array Response with a "removed" list
     */
    public function bulkRemovePrograms(string $orgUnitId, string $catalogId, array $programs): array
    {
        return $this->sendPost(
            "organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs/bulkremove",
            $this->catalogProgramRefs($programs)
        );
    }

    /**
     * Body for bulkadd/bulkremove: a bare JSON array of {supplierId, programId}
     *
     * The client used to wrap it in {"programs": [...]} and its docblock asked for a
     * clientId per item; the spec's schema is a bare array closed to other fields (#3),
     * so any other key (clientId included) is dropped.
     *
     * @param array $programs
     * @return array
     */
    protected function catalogProgramRefs(array $programs): array
    {
        return array_values(array_map(
            fn($program) => [
                'supplierId' => $program['supplierId'] ?? null,
                'programId' => $program['programId'] ?? null,
            ],
            $programs
        ));
    }

    /**
     * List the programs in a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return array Items with status, supplierId, programId, clientId, lastChange
     */
    public function listStaticCatalogPrograms(string $orgUnitId, string $catalogId): array
    {
        return $this->sendGet("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs");
    }

    /**
     * Changes to a static catalog since a moment (the API allows at most 90 days back)
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param DateTimeInterface|string $since A date-time, or an ISO 8601 string passed as is
     * @return array Items with status (active, inactive, removed), supplierId, programId, clientId, lastChange
     */
    public function getStaticCatalogChanges(string $orgUnitId, string $catalogId, DateTimeInterface|string $since): array
    {
        return $this->sendGet(
            "organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/changes",
            ['changesSince' => self::formatDateTime($since)]
        );
    }

    /**
     * List dynamic catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array<DynamicCatalog>
     */
    public function listDynamicCatalogs(string $orgUnitId): array
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs");
        $items = $this->extractListItems($response, 'catalogs');

        return $this->hydrateModels(DynamicCatalog::class, $items);
    }

    /**
     * Get a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return DynamicCatalog
     */
    public function getDynamicCatalog(string $orgUnitId, string $catalogId): DynamicCatalog
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}");
        return $this->hydrateModel(DynamicCatalog::class, $response);
    }

    /**
     * Create a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $title
     * @param string $clientId
     * @param string|null $regionFilter Postal code range filter (e.g., "1000-1999,2345")
     * @return DynamicCatalog
     */
    public function createDynamicCatalog(
        string $orgUnitId,
        string $title,
        string $clientId,
        ?string $regionFilter = null
    ): DynamicCatalog {
        $this->validateRequired(compact('orgUnitId', 'title', 'clientId'), ['orgUnitId', 'title', 'clientId']);

        $data = [
            'title' => $title,
            'clientId' => $clientId,
        ];

        if ($regionFilter !== null) {
            $data['regionFilter'] = $regionFilter;
        }

        $response = $this->sendPost("organizations/{$orgUnitId}/dynamiccatalogs", $data);

        return $this->hydrateModel(DynamicCatalog::class, $response);
    }

    /**
     * Update a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string|null $title
     * @param string|null $regionFilter
     * @return DynamicCatalog
     */
    public function updateDynamicCatalog(
        string $orgUnitId,
        string $catalogId,
        ?string $title = null,
        ?string $regionFilter = null
    ): DynamicCatalog {
        $this->validateRequired(compact('orgUnitId', 'catalogId'), ['orgUnitId', 'catalogId']);

        $data = [];
        if ($title !== null) {
            $data['title'] = $title;
        }
        if ($regionFilter !== null) {
            $data['regionFilter'] = $regionFilter;
        }

        $response = $this->sendPatch("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}", $data);

        return $this->hydrateModel(DynamicCatalog::class, $response);
    }

    /**
     * Delete a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return void
     */
    public function deleteDynamicCatalog(string $orgUnitId, string $catalogId): void
    {
        $this->sendDelete("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}");
    }

    /**
     * Add supplier to dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $supplierId
     * @param string $selection "all" or "onlyincompanyordiscount"
     * @return array supplierId and selection
     */
    public function addSupplierToDynamicCatalog(
        string $orgUnitId,
        string $catalogId,
        string $supplierId,
        string $selection = 'all'
    ): array {
        # The spec requires a "selection"; an empty body used to be sent (#3)
        return $this->sendPut(
            "organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}",
            ['selection' => $selection]
        );
    }

    /**
     * List the suppliers of a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return array Items with supplierId and selection
     */
    public function listDynamicCatalogSuppliers(string $orgUnitId, string $catalogId): array
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers");
        return $this->extractListItems($response, 'suppliers');
    }

    /**
     * Get one supplier of a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $supplierId
     * @return array supplierId and selection
     */
    public function getDynamicCatalogSupplier(string $orgUnitId, string $catalogId, string $supplierId): array
    {
        return $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}");
    }

    /**
     * List the programs in a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return array Items with status, supplierId, programId, clientId, lastChange
     */
    public function listDynamicCatalogPrograms(string $orgUnitId, string $catalogId): array
    {
        return $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/programs");
    }

    /**
     * Changes to a dynamic catalog since a moment (the API allows at most 90 days back)
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param DateTimeInterface|string $since A date-time, or an ISO 8601 string passed as is
     * @return array Items with status (active, inactive, removed), supplierId, programId, clientId, lastChange
     */
    public function getDynamicCatalogChanges(string $orgUnitId, string $catalogId, DateTimeInterface|string $since): array
    {
        return $this->sendGet(
            "organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/changes",
            ['changesSince' => self::formatDateTime($since)]
        );
    }

    /**
     * Format a moment for the API's date-time query parameters: UTC, "Z" suffix
     *
     * @param DateTimeInterface|string $value
     * @return string
     */
    protected static function formatDateTime(DateTimeInterface|string $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\\TH:i:s\\Z');
    }

    /**
     * Remove supplier from dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $supplierId
     * @return void
     */
    public function removeSupplierFromDynamicCatalog(string $orgUnitId, string $catalogId, string $supplierId): void
    {
        $this->sendDelete("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}");
    }

    /**
     * List webhooks for an organization
     *
     * @param string $orgUnitId
     * @return array<Webhook>
     */
    public function listWebhooks(string $orgUnitId): array
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/webhooks");
        $items = $this->extractListItems($response, 'webhooks');

        return $this->hydrateModels(Webhook::class, $items);
    }

    /**
     * Get a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @return Webhook
     */
    public function getWebhook(string $orgUnitId, string $webhookId): Webhook
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/webhooks/{$webhookId}");
        return $this->hydrateModel(Webhook::class, $response);
    }

    /**
     * Create a webhook
     *
     * @param string $orgUnitId
     * @param string $url Webhook URL to call
     * @param array $events Events to listen to (e.g., ['catalog', 'program'])
     * @return Webhook
     */
    public function createWebhook(string $orgUnitId, string $url, array $events): Webhook
    {
        $this->validateRequired(compact('orgUnitId', 'url', 'events'), ['orgUnitId', 'url', 'events']);

        $response = $this->sendPost("organizations/{$orgUnitId}/webhooks", [
            'url' => $url,
            'events' => $events,
        ]);

        return $this->hydrateModel(Webhook::class, $response);
    }

    /**
     * Update a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @param string|null $url
     * @param array|null $events
     * @param bool|null $active
     * @return Webhook
     */
    public function updateWebhook(
        string $orgUnitId,
        string $webhookId,
        ?string $url = null,
        ?array $events = null,
        ?bool $active = null
    ): Webhook {
        $this->validateRequired(compact('orgUnitId', 'webhookId'), ['orgUnitId', 'webhookId']);

        $data = [];
        if ($url !== null) {
            $data['url'] = $url;
        }
        if ($events !== null) {
            $data['events'] = $events;
        }
        if ($active !== null) {
            $data['active'] = $active;
        }

        $response = $this->sendPatch("organizations/{$orgUnitId}/webhooks/{$webhookId}", $data);

        return $this->hydrateModel(Webhook::class, $response);
    }

    /**
     * Delete a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @return void
     */
    public function deleteWebhook(string $orgUnitId, string $webhookId): void
    {
        $this->sendDelete("organizations/{$orgUnitId}/webhooks/{$webhookId}");
    }

    /**
     * Test a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @param string $event Event to simulate; "catalog" is the only one the spec defines
     * @return array Test result
     */
    public function testWebhook(string $orgUnitId, string $webhookId, string $event = 'catalog'): array
    {
        # The spec requires {"event": ...}; an empty body used to be sent (#3)
        return $this->sendPost("organizations/{$orgUnitId}/webhooks/{$webhookId}/test", ['event' => $event]);
    }
}
