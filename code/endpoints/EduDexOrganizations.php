<?php

/**
 * Organizations endpoint
 *
 * Manages organizations, catalogs, and webhooks
 */
class EduDexOrganizations extends EduDexBaseEndpoint
{
    /**
     * List all organizations
     *
     * @return array<Organization>
     */
    public function list()
    {
        $response = $this->sendGet('organizations');
        $items = $this->extractListItems($response, 'organizations');

        return $this->hydrateModels('Organization', $items);
    }

    /**
     * Get a single organization
     *
     * @param string $orgUnitId
     * @return EduDexOrganization
     */
    public function get($orgUnitId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}");
        return $this->hydrateModel('Organization', $response);
    }

    /**
     * List all catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array
     */
    public function listCatalogs($orgUnitId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/catalogs");
        return $this->extractListItems($response, 'catalogs');
    }

    /**
     * List static catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array<StaticCatalog>
     */
    public function listStaticCatalogs($orgUnitId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/staticcatalogs");
        $items = $this->extractListItems($response, 'catalogs');

        return $this->hydrateModels('StaticCatalog', $items);
    }

    /**
     * Get a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return EduDexStaticCatalog
     */
    public function getStaticCatalog($orgUnitId, $catalogId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}");
        return $this->hydrateModel('StaticCatalog', $response);
    }

    /**
     * Create a static catalog
     *
     * @param string $orgUnitId
     * @param string $title
     * @param string $clientId
     * @return EduDexStaticCatalog
     */
    public function createStaticCatalog($orgUnitId, $title, $clientId)
    {
        $this->validateRequired(compact('orgUnitId', 'title', 'clientId'), array('orgUnitId', 'title', 'clientId'));

        $response = $this->sendPost("organizations/{$orgUnitId}/staticcatalogs", array(
            'title' => $title,
            'clientId' => $clientId,
        ));

        return $this->hydrateModel('StaticCatalog', $response);
    }

    /**
     * Update a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $title
     * @return EduDexStaticCatalog
     */
    public function updateStaticCatalog($orgUnitId, $catalogId, $title)
    {
        $this->validateRequired(compact('orgUnitId', 'catalogId', 'title'), array('orgUnitId', 'catalogId', 'title'));

        $response = $this->sendPatch("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}", array(
            'title' => $title,
        ));

        return $this->hydrateModel('StaticCatalog', $response);
    }

    /**
     * Delete a static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return void
     */
    public function deleteStaticCatalog($orgUnitId, $catalogId)
    {
        $this->sendDelete("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}");
    }

    /**
     * Bulk add programs to static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param array $programs Array of ['supplierId' => string, 'programId' => string, 'clientId' => string]
     * @return array Response with success/failure counts
     */
    public function bulkAddPrograms($orgUnitId, $catalogId, $programs)
    {
        return $this->sendPost("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs/bulkadd", array(
            'programs' => $programs,
        ));
    }

    /**
     * Bulk remove programs from static catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param array $programs Array of ['supplierId' => string, 'programId' => string, 'clientId' => string]
     * @return array Response with success/failure counts
     */
    public function bulkRemovePrograms($orgUnitId, $catalogId, $programs)
    {
        return $this->sendPost("organizations/{$orgUnitId}/staticcatalogs/{$catalogId}/programs/bulkremove", array(
            'programs' => $programs,
        ));
    }

    /**
     * List dynamic catalogs for an organization
     *
     * @param string $orgUnitId
     * @return array<DynamicCatalog>
     */
    public function listDynamicCatalogs($orgUnitId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs");
        $items = $this->extractListItems($response, 'catalogs');

        return $this->hydrateModels('DynamicCatalog', $items);
    }

    /**
     * Get a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return EduDexDynamicCatalog
     */
    public function getDynamicCatalog($orgUnitId, $catalogId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}");
        return $this->hydrateModel('DynamicCatalog', $response);
    }

    /**
     * Create a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $title
     * @param string $clientId
     * @param string|null $regionFilter Postal code range filter (e.g., "1000-1999,2345")
     * @return EduDexDynamicCatalog
     */
    public function createDynamicCatalog(
        $orgUnitId,
        $title,
        $clientId,
        $regionFilter = null
    ) {
        $this->validateRequired(compact('orgUnitId', 'title', 'clientId'), array('orgUnitId', 'title', 'clientId'));

        $data = array(
            'title' => $title,
            'clientId' => $clientId,
        );

        if ($regionFilter !== null) {
            $data['regionFilter'] = $regionFilter;
        }

        $response = $this->sendPost("organizations/{$orgUnitId}/dynamiccatalogs", $data);

        return $this->hydrateModel('DynamicCatalog', $response);
    }

    /**
     * Update a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string|null $title
     * @param string|null $regionFilter
     * @return EduDexDynamicCatalog
     */
    public function updateDynamicCatalog(
        $orgUnitId,
        $catalogId,
        $title = null,
        $regionFilter = null
    ) {
        $this->validateRequired(compact('orgUnitId', 'catalogId'), array('orgUnitId', 'catalogId'));

        $data = array();
        if ($title !== null) {
            $data['title'] = $title;
        }
        if ($regionFilter !== null) {
            $data['regionFilter'] = $regionFilter;
        }

        $response = $this->sendPatch("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}", $data);

        return $this->hydrateModel('DynamicCatalog', $response);
    }

    /**
     * Delete a dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @return void
     */
    public function deleteDynamicCatalog($orgUnitId, $catalogId)
    {
        $this->sendDelete("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}");
    }

    /**
     * Add supplier to dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $supplierId
     * @return array
     */
    public function addSupplierToDynamicCatalog($orgUnitId, $catalogId, $supplierId)
    {
        return $this->sendPut("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}", array());
    }

    /**
     * Remove supplier from dynamic catalog
     *
     * @param string $orgUnitId
     * @param string $catalogId
     * @param string $supplierId
     * @return void
     */
    public function removeSupplierFromDynamicCatalog($orgUnitId, $catalogId, $supplierId)
    {
        $this->sendDelete("organizations/{$orgUnitId}/dynamiccatalogs/{$catalogId}/suppliers/{$supplierId}");
    }

    /**
     * List webhooks for an organization
     *
     * @param string $orgUnitId
     * @return array<Webhook>
     */
    public function listWebhooks($orgUnitId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/webhooks");
        $items = $this->extractListItems($response, 'webhooks');

        return $this->hydrateModels('Webhook', $items);
    }

    /**
     * Get a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @return EduDexWebhook
     */
    public function getWebhook($orgUnitId, $webhookId)
    {
        $response = $this->sendGet("organizations/{$orgUnitId}/webhooks/{$webhookId}");
        return $this->hydrateModel('Webhook', $response);
    }

    /**
     * Create a webhook
     *
     * @param string $orgUnitId
     * @param string $url Webhook URL to call
     * @param array $events Events to listen to (e.g., ['catalog', 'program'])
     * @return EduDexWebhook
     */
    public function createWebhook($orgUnitId, $url, $events)
    {
        $this->validateRequired(compact('orgUnitId', 'url', 'events'), array('orgUnitId', 'url', 'events'));

        $response = $this->sendPost("organizations/{$orgUnitId}/webhooks", array(
            'url' => $url,
            'events' => $events,
        ));

        return $this->hydrateModel('Webhook', $response);
    }

    /**
     * Update a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @param string|null $url
     * @param array|null $events
     * @param bool|null $active
     * @return EduDexWebhook
     */
    public function updateWebhook(
        $orgUnitId,
        $webhookId,
        $url = null,
        $events = null,
        $active = null
    ) {
        $this->validateRequired(compact('orgUnitId', 'webhookId'), array('orgUnitId', 'webhookId'));

        $data = array();
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

        return $this->hydrateModel('Webhook', $response);
    }

    /**
     * Delete a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @return void
     */
    public function deleteWebhook($orgUnitId, $webhookId)
    {
        $this->sendDelete("organizations/{$orgUnitId}/webhooks/{$webhookId}");
    }

    /**
     * Test a webhook
     *
     * @param string $orgUnitId
     * @param string $webhookId
     * @return array Test result
     */
    public function testWebhook($orgUnitId, $webhookId)
    {
        return $this->sendPost("organizations/{$orgUnitId}/webhooks/{$webhookId}/test", array());
    }
}
