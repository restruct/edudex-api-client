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
        $response = $this->sendGet('suppliers');
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
        $response = $this->sendGet("suppliers/{$orgUnitId}");
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
        return $this->sendGet("suppliers/{$orgUnitId}/metadata");
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

        return $this->sendPut("suppliers/{$orgUnitId}/metadata", $metadata);
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
        $response = $this->sendGet("suppliers/{$orgUnitId}/programs", $query);

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
        $response = $this->sendGet("suppliers/{$orgUnitId}/programs/{$programId}", $query);

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

        # The client id is a path segment (spec: clientIdOrPublic, so "public" works too).
        # It used to go in a query array as a 4th argument sendPut() does not take, so PHP
        # dropped it and the request hit .../programs/{programId}, which only has GET (#1)
        $response = $this->sendPut("suppliers/{$orgUnitId}/programs/{$programId}/{$clientId}", $programData);

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

        # Client id in the path, as for upsertProgram() (#1)
        $this->sendDelete("suppliers/{$orgUnitId}/programs/{$programId}/{$clientId}");
    }

    /**
     * List discounts for a supplier
     *
     * @param string $orgUnitId
     * @return array List of discount clients
     */
    public function listDiscounts(string $orgUnitId): array
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
    public function getDiscounts(string $orgUnitId, string $clientId): array
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
    public function upsertDiscounts(string $orgUnitId, string $clientId, array $discounts): array
    {
        $this->validateRequired(compact('orgUnitId', 'clientId', 'discounts'), ['orgUnitId', 'clientId', 'discounts']);

        return $this->sendPut("suppliers/{$orgUnitId}/discounts/{$clientId}", $discounts);
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
        $this->sendDelete("suppliers/{$orgUnitId}/discounts/{$clientId}");
    }
}
