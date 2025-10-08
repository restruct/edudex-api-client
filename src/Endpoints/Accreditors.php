<?php

namespace Restruct\EduDex\Endpoints;

use Restruct\EduDex\Models\Accreditation;
use Restruct\EduDex\Models\Accreditor;

/**
 * Accreditors endpoint
 *
 * Manages accreditors and their accreditations
 *
 * @package Restruct\EduDex\Endpoints
 */
class Accreditors extends BaseEndpoint
{
    /**
     * List all accreditors
     *
     * @return array<Accreditor>
     */
    public function list(): array
    {
        $response = $this->get('accreditors');
        $items = $this->extractListItems($response, 'accreditors');

        return $this->hydrateModels(Accreditor::class, $items);
    }

    /**
     * Get a single accreditor
     *
     * @param string $orgUnitId
     * @return Accreditor
     */
    public function get(string $orgUnitId): Accreditor
    {
        $response = $this->get("accreditors/{$orgUnitId}");
        return $this->hydrateModel(Accreditor::class, $response);
    }

    /**
     * List accreditations for an accreditor
     *
     * @param string $orgUnitId
     * @return array<Accreditation>
     */
    public function listAccreditations(string $orgUnitId): array
    {
        $response = $this->get("accreditors/{$orgUnitId}/accreditations");
        $items = $this->extractListItems($response, 'accreditations');

        return $this->hydrateModels(Accreditation::class, $items);
    }

    /**
     * Get a specific accreditation
     *
     * @param string $orgUnitId
     * @param string $accreditationId
     * @return Accreditation
     */
    public function getAccreditation(string $orgUnitId, string $accreditationId): Accreditation
    {
        $response = $this->get("accreditors/{$orgUnitId}/accreditations/{$accreditationId}");
        return $this->hydrateModel(Accreditation::class, $response);
    }

    /**
     * Create an accreditation
     *
     * @param string $orgUnitId Accreditor ID
     * @param array $data Accreditation data with orgUnitId (supplier), accreditation type, validFrom, validUntil
     * @return Accreditation
     */
    public function createAccreditation(string $orgUnitId, array $data): Accreditation
    {
        $this->validateRequired(
            array_merge(['orgUnitId' => $orgUnitId], $data),
            ['orgUnitId', 'accreditation', 'validFrom', 'validUntil']
        );

        $response = $this->post("accreditors/{$orgUnitId}/accreditations", $data);
        return $this->hydrateModel(Accreditation::class, $response);
    }

    /**
     * Update an accreditation
     *
     * @param string $orgUnitId Accreditor ID
     * @param string $accreditationId
     * @param array $data Partial accreditation data to update
     * @return Accreditation
     */
    public function updateAccreditation(string $orgUnitId, string $accreditationId, array $data): Accreditation
    {
        $this->validateRequired(
            compact('orgUnitId', 'accreditationId'),
            ['orgUnitId', 'accreditationId']
        );

        $response = $this->patch("accreditors/{$orgUnitId}/accreditations/{$accreditationId}", $data);
        return $this->hydrateModel(Accreditation::class, $response);
    }

    /**
     * Delete an accreditation
     *
     * @param string $orgUnitId
     * @param string $accreditationId
     * @return void
     */
    public function deleteAccreditation(string $orgUnitId, string $accreditationId): void
    {
        $this->delete("accreditors/{$orgUnitId}/accreditations/{$accreditationId}");
    }
}
