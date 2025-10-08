<?php

namespace Restruct\EduDex\Endpoints;

use Restruct\EduDex\Models\Program;
use Restruct\EduDex\Models\Supplier;

/**
 * Suppliers endpoint
 *
 * Manages suppliers, their programs, discounts, and metadata
 *
 * @package Restruct\EduDex\Endpoints
 */
class Suppliers extends BaseEndpoint
{
    /**
     * List all suppliers
     *
     * @return array<Supplier>
     */
    public function list(): array
    {
        $response = $this->get('suppliers');
        $items = $this->extractListItems($response, 'suppliers');

        return $this->hydrateModels(Supplier::class, $items);
    }

    /**
     * Get a single supplier
     *
     * @param string $orgUnitId
     * @return Supplier
     */
    public function get(string $orgUnitId): Supplier
    {
        $response = $this->get("suppliers/{$orgUnitId}");
        return $this->hydrateModel(Supplier::class, $response);
    }

    /**
     * Get supplier metadata
     *
     * @param string $orgUnitId
     * @return array Institute metadata
     */
    public function getMetadata(string $orgUnitId): array
    {
        return $this->get("suppliers/{$orgUnitId}/metadata");
    }

    /**
     * Update supplier metadata
     *
     * @param string $orgUnitId
     * @param array $metadata Institute metadata (full structure)
     * @return array Updated metadata
     */
    public function updateMetadata(string $orgUnitId, array $metadata): array
    {
        $this->validateRequired(compact('orgUnitId', 'metadata'), ['orgUnitId', 'metadata']);

        return $this->put("suppliers/{$orgUnitId}/metadata", $metadata);
    }

    /**
     * List programs for a supplier
     *
     * @param string $orgUnitId
     * @param string|null $clientId Optional client ID filter
     * @return array Program list with identifiers
     */
    public function listPrograms(string $orgUnitId, ?string $clientId = null): array
    {
        $query = $this->buildQuery(['clientId' => $clientId]);
        $response = $this->get("suppliers/{$orgUnitId}/programs", $query);

        return $this->extractListItems($response, 'programs');
    }

    /**
     * Get a specific program
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @return Program
     */
    public function getProgram(string $orgUnitId, string $programId, string $clientId): Program
    {
        $this->validateRequired(compact('orgUnitId', 'programId', 'clientId'), ['orgUnitId', 'programId', 'clientId']);

        $query = ['clientId' => $clientId];
        $response = $this->get("suppliers/{$orgUnitId}/programs/{$programId}", $query);

        return $this->hydrateModel(Program::class, $response);
    }

    /**
     * Create or update a program
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @param array $programData Full program data structure
     * @return Program
     */
    public function upsertProgram(string $orgUnitId, string $programId, string $clientId, array $programData): Program
    {
        $this->validateRequired(
            compact('orgUnitId', 'programId', 'clientId', 'programData'),
            ['orgUnitId', 'programId', 'clientId', 'programData']
        );

        $query = ['clientId' => $clientId];
        $response = $this->put("suppliers/{$orgUnitId}/programs/{$programId}", $programData, [], $query);

        return $this->hydrateModel(Program::class, $response);
    }

    /**
     * Delete a program
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @return void
     */
    public function deleteProgram(string $orgUnitId, string $programId, string $clientId): void
    {
        $this->validateRequired(compact('orgUnitId', 'programId', 'clientId'), ['orgUnitId', 'programId', 'clientId']);

        $query = ['clientId' => $clientId];
        $this->delete("suppliers/{$orgUnitId}/programs/{$programId}", [], $query);
    }

    /**
     * List discounts for a supplier
     *
     * @param string $orgUnitId
     * @return array List of discount clients
     */
    public function listDiscounts(string $orgUnitId): array
    {
        $response = $this->get("suppliers/{$orgUnitId}/discounts");
        return $this->extractListItems($response, 'clients');
    }

    /**
     * Get discounts for a specific client
     *
     * @param string $orgUnitId
     * @param string $clientId
     * @return array Discount data
     */
    public function getDiscounts(string $orgUnitId, string $clientId): array
    {
        return $this->get("suppliers/{$orgUnitId}/discounts/{$clientId}");
    }

    /**
     * Create or update discounts for a client
     *
     * @param string $orgUnitId
     * @param string $clientId
     * @param array $discounts Discount data structure
     * @return array Updated discount data
     */
    public function upsertDiscounts(string $orgUnitId, string $clientId, array $discounts): array
    {
        $this->validateRequired(compact('orgUnitId', 'clientId', 'discounts'), ['orgUnitId', 'clientId', 'discounts']);

        return $this->put("suppliers/{$orgUnitId}/discounts/{$clientId}", $discounts);
    }

    /**
     * Delete discounts for a client
     *
     * @param string $orgUnitId
     * @param string $clientId
     * @return void
     */
    public function deleteDiscounts(string $orgUnitId, string $clientId): void
    {
        $this->delete("suppliers/{$orgUnitId}/discounts/{$clientId}");
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
    protected function put(string $path, array $data = [], array $headers = [], array $query = []): array
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return parent::put($path, $data, $headers);
    }

    /**
     * Helper method to modify DELETE request to support query params
     *
     * @param string $path
     * @param array $headers
     * @param array $query
     * @return array
     */
    protected function delete(string $path, array $headers = [], array $query = []): array
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return parent::delete($path, $headers);
    }
}
