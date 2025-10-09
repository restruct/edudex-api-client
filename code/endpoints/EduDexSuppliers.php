<?php

/**
 * Suppliers endpoint
 *
 * Manages suppliers, their programs, discounts, and metadata
 */
class EduDexSuppliers extends EduDexBaseEndpoint
{
    /**
     * List all suppliers
     *
     * @return array<Supplier>
     */
    public function list()
    {
        $response = $this->sendGet('suppliers');
        $items = $this->extractListItems($response, 'suppliers');

        return $this->hydrateModels('Supplier', $items);
    }

    /**
     * Get a single supplier
     *
     * @param string $orgUnitId
     * @return EduDexSupplier
     */
    public function get($orgUnitId)
    {
        $response = $this->sendGet("suppliers/{$orgUnitId}");
        return $this->hydrateModel('Supplier', $response);
    }

    /**
     * Get supplier metadata
     *
     * @param string $orgUnitId
     * @return array Institute metadata
     */
    public function getMetadata($orgUnitId)
    {
        return $this->sendGet("suppliers/{$orgUnitId}/metadata");
    }

    /**
     * Update supplier metadata
     *
     * @param string $orgUnitId
     * @param array $metadata Institute metadata (full structure)
     * @return array Updated metadata
     */
    public function updateMetadata($orgUnitId, $metadata)
    {
        $this->validateRequired(compact('orgUnitId', 'metadata'), array('orgUnitId', 'metadata'));

        return $this->sendPut("suppliers/{$orgUnitId}/metadata", $metadata);
    }

    /**
     * List programs for a supplier
     *
     * @param string $orgUnitId
     * @param string|null $clientId Optional client ID filter
     * @return array Program list with identifiers
     */
    public function listPrograms($orgUnitId, $clientId = null)
    {
        $query = $this->buildQuery(array('clientId' => $clientId));
        $response = $this->sendGet("suppliers/{$orgUnitId}/programs", $query);

        return $this->extractListItems($response, 'programs');
    }

    /**
     * Get a specific program
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @return EduDexProgram
     */
    public function getProgram($orgUnitId, $programId, $clientId)
    {
        $this->validateRequired(compact('orgUnitId', 'programId', 'clientId'), array('orgUnitId', 'programId', 'clientId'));

        $query = array('clientId' => $clientId);
        $response = $this->sendGet("suppliers/{$orgUnitId}/programs/{$programId}", $query);

        return $this->hydrateModel('Program', $response);
    }

    /**
     * Create or update a program
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @param array $programData Full program data structure
     * @return EduDexProgram
     */
    public function upsertProgram($orgUnitId, $programId, $clientId, $programData)
    {
        $this->validateRequired(
            compact('orgUnitId', 'programId', 'clientId', 'programData'),
            array('orgUnitId', 'programId', 'clientId', 'programData')
        );

        $query = array('clientId' => $clientId);
        $response = $this->sendPut("suppliers/{$orgUnitId}/programs/{$programId}", $programData, array(), $query);

        return $this->hydrateModel('Program', $response);
    }

    /**
     * Delete a program
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @return void
     */
    public function deleteProgram($orgUnitId, $programId, $clientId)
    {
        $this->validateRequired(compact('orgUnitId', 'programId', 'clientId'), array('orgUnitId', 'programId', 'clientId'));

        $query = array('clientId' => $clientId);
        $this->sendDelete("suppliers/{$orgUnitId}/programs/{$programId}", array(), $query);
    }

    /**
     * List discounts for a supplier
     *
     * @param string $orgUnitId
     * @return array List of discount clients
     */
    public function listDiscounts($orgUnitId)
    {
        $response = $this->sendGet("suppliers/{$orgUnitId}/discounts");
        return $this->extractListItems($response, 'clients');
    }

    /**
     * Get discounts for a specific client
     *
     * @param string $orgUnitId
     * @param string $clientId
     * @return array Discount data
     */
    public function getDiscounts($orgUnitId, $clientId)
    {
        return $this->sendGet("suppliers/{$orgUnitId}/discounts/{$clientId}");
    }

    /**
     * Create or update discounts for a client
     *
     * @param string $orgUnitId
     * @param string $clientId
     * @param array $discounts Discount data structure
     * @return array Updated discount data
     */
    public function upsertDiscounts($orgUnitId, $clientId, $discounts)
    {
        $this->validateRequired(compact('orgUnitId', 'clientId', 'discounts'), array('orgUnitId', 'clientId', 'discounts'));

        return $this->sendPut("suppliers/{$orgUnitId}/discounts/{$clientId}", $discounts);
    }

    /**
     * Delete discounts for a client
     *
     * @param string $orgUnitId
     * @param string $clientId
     * @return void
     */
    public function deleteDiscounts($orgUnitId, $clientId)
    {
        $this->sendDelete("suppliers/{$orgUnitId}/discounts/{$clientId}");
    }

    /**
     * Helper method to modify PUT request to support query params
     *
     * @param string $path
     * @param array $data
     * @param array $headers
     * @param array $query
     * @return array
     */
    protected function sendPut($path, $data = array(), $headers = array(), $query = array())
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return parent::sendPut($path, $data, $headers);
    }

    /**
     * Helper method to modify DELETE request to support query params
     *
     * @param string $path
     * @param array $headers
     * @param array $query
     * @return array
     */
    protected function sendDelete($path, $headers = array(), $query = array())
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return parent::sendDelete($path, $headers);
    }
}
