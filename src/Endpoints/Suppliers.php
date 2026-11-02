<?php

namespace Restruct\EduDex\Endpoints;

use Restruct\EduDex\Exceptions\ApiException;
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
     * @param string|null $clientId Optional client ID filter (applied client-side)
     * @return array Program list with identifiers: orgUnitId, programId, clientId, lastChange
     */
    public function listPrograms(string $orgUnitId, ?string $clientId = null): array
    {
        # The spec defines no clientId query parameter, so the API ignored the filter it
        # used to be sent as; the list sits under "items", not "programs" (#3)
        $response = $this->sendGet("suppliers/{$orgUnitId}/programs");
        $items = $response['items'] ?? $this->extractListItems($response, 'programs');

        if ($clientId === null) {
            return $items;
        }

        return array_values(array_filter($items, fn($item) => ($item['clientId'] ?? null) === $clientId));
    }

    /**
     * List the clients a program is published for
     *
     * @param string $orgUnitId
     * @param string $programId
     * @return array Items with orgUnitId, programId, clientId, lastChange
     */
    public function listProgramClients(string $orgUnitId, string $programId): array
    {
        $response = $this->sendGet("suppliers/{$orgUnitId}/programs/{$programId}");

        return $response['items'] ?? [];
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

        // $query = ['clientId' => $clientId];
        // $response = $this->sendGet("suppliers/{$orgUnitId}/programs/{$programId}", $query);
        //
        // return $this->hydrateModel(Program::class, $response);
        # The spec's GET on .../programs/{programId} lists the clients the program is
        # published for (see listProgramClients()); one program's data is only available
        # through POST /programs/bulk (#3)
        $bulk = new Programs($this->client, $this->logger);
        $response = $bulk->bulk([compact('orgUnitId', 'programId', 'clientId')]);

        $successful = $bulk->getSuccessful($response);
        if ($successful !== []) {
            return $successful[0];
        }

        $failed = $bulk->getFailed($response)[0] ?? compact('orgUnitId', 'programId', 'clientId');
        throw ApiException::badRequest(
            sprintf('Program %s/%s/%s could not be fetched: %s', $orgUnitId, $programId, $clientId, $failed['error'] ?? 'no data returned'),
            $failed
        );
    }

    /**
     * Create or update a program
     *
     * Returns the program as submitted. The API answers with a status object, not the
     * program; use upsertProgramWithStatus() to get that status and its validation messages.
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId
     * @param array $programData Full program data structure
     * @return Program
     */
    public function upsertProgram(string $orgUnitId, string $programId, string $clientId, array $programData): Program
    {
        $this->upsertProgramWithStatus($orgUnitId, $programId, $clientId, $programData);

        // return $this->hydrateModel(Program::class, $response);
        # Hydrating the status response left every typed Program property unset, so reading
        # e.g. ->orgUnitId threw an Error (#3). Build it from what was stored instead.
        return Program::fromArray(array_merge(
            $programData,
            compact('orgUnitId', 'programId', 'clientId'),
            ['programData' => $programData]
        ));
    }

    /**
     * Create or update a program, returning the API's status
     *
     * @param string $orgUnitId
     * @param string $programId
     * @param string $clientId Client id, or "public"
     * @param array $programData Full program data structure
     * @return array status ("ok" or "unchanged"), validationMessages, lastChange
     * @throws \Restruct\EduDex\Exceptions\ValidationException When the API rejects the program (400)
     */
    public function upsertProgramWithStatus(string $orgUnitId, string $programId, string $clientId, array $programData): array
    {
        $this->validateRequired(
            compact('orgUnitId', 'programId', 'clientId', 'programData'),
            ['orgUnitId', 'programId', 'clientId', 'programData']
        );

        # The client id is a path segment (spec: clientIdOrPublic, so "public" works too).
        # It used to go in a query array as a 4th argument sendPut() does not take, so PHP
        # dropped it and the request hit .../programs/{programId}, which only has GET (#1)
        return $this->sendPut("suppliers/{$orgUnitId}/programs/{$programId}/{$clientId}", $programData);
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
        # The spec's list is under "items"; "clients" is kept as a fallback (#3)
        return $response['items'] ?? $this->extractListItems($response, 'clients');
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
