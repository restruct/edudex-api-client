<?php

namespace Restruct\EduDex\Endpoints;

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
        $response = $this->get('organizations');
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
        $response = $this->get("organizations/{$orgUnitId}");
        return $this->hydrateModel(Organization::class, $response);
    }

    /**
     * List all catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array
     */
    public function listCatalogs(string $orgUnitId): array
    {
        $response = $this->get("organizations/{$orgUnitId}/catalogs");
        return $this->extractListItems($response, 'catalogs');
    }

    /**
     * List static catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array<StaticCatalog>
     */
    public function listStaticCatalogs(string $orgUnitId): array
    {
        $response = $this->get("organizations/{$orgUnitId}/staticcatalogs");
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
        $response = $this->get("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}");
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

        $response = $this->post("organizations/{$orgUnitId}/staticcatalogs", [
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

        $response = $this->patch("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}", [
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
        $this->delete("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}");
    }

    /**
     * Bulk add programs to static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param array $programs Array of ['supplierId' => string, 'programId' => string, 'clientId' => string]
     * @return array Response with success/failure counts
     */
    public function bulkAddPrograms(string $orgUnitId, string $catalogId, array $programs): array
    {
        return $this->post("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs/bulkadd", [
            'programs' => $programs,
        ]);
    }

    /**
     * Bulk remove programs from static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param array $programs Array of ['supplierId' => string, 'programId' => string, 'clientId' => string]
     * @return array Response with success/failure counts
     */
    public function bulkRemovePrograms(string $orgUnitId, string $catalogId, array $programs): array
    {
        return $this->post("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs/bulkremove", [
            'programs' => $programs,
        ]);
    }

    /**
     * List dynamic catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array<DynamicCatalog>
     */
    public function listDynamicCatalogs(string $orgUnitId): array
    {
        $response = $this->get("organizations/{$orgUnitId}/dynamiccatalogs");
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
        $response = $this->get("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}");
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

        $response = $this->post("organizations/{$orgUnitId}/dynamiccatalogs", $data);

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

        $response = $this->patch("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}", $data);

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
        $this->delete("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}");
    }

    /**
     * Add supplier to dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $supplierId
     * @return array
     */
    public function addSupplierToDynamicCatalog(string $orgUnitId, string $catalogId, string $supplierId): array
    {
        return $this->put("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}", []);
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
        $this->delete("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}");
    }

    /**
     * List webhooks for an organization
     *
     * @param string $orgUnitId
     * @return array<Webhook>
     */
    public function listWebhooks(string $orgUnitId): array
    {
        $response = $this->get("organizations/{$orgUnitId}/webhooks");
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
        $response = $this->get("organizations/{$orgUnitId}/webhooks/{$webhookId}");
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

        $response = $this->post("organizations/{$orgUnitId}/webhooks", [
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

        $response = $this->patch("organizations/{$orgUnitId}/webhooks/{$webhookId}", $data);

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
        $this->delete("organizations/{$orgUnitId}/webhooks/{$webhookId}");
    }

    /**
     * Test a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @return array Test result
     */
    public function testWebhook(string $orgUnitId, string $webhookId): array
    {
        return $this->post("organizations/{$orgUnitId}/webhooks/{$webhookId}/test", []);
    }
}
