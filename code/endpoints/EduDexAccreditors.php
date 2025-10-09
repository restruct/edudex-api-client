<?php

/**
 * Accreditors endpoint
 *
 * Manages accreditors and their accreditations
 */
class EduDexAccreditors extends EduDexBaseEndpoint
{
    /**
     * List all accreditors
     *
     * @return array<Accreditor>
     */
    public function list()
    {
        $response = $this->sendGet('accreditors');
        $items = $this->extractListItems($response, 'accreditors');

        return $this->hydrateModels('Accreditor', $items);
    }

    /**
     * Get a single accreditor
     *
     * @param string $orgUnitId
     * @return EduDexAccreditor
     */
    public function get($orgUnitId)
    {
        $response = $this->sendGet("accreditors/{$orgUnitId}");
        return $this->hydrateModel('Accreditor', $response);
    }

    /**
     * List accreditations for an accreditor
     *
     * @param string $orgUnitId
     * @return array<Accreditation>
     */
    public function listAccreditations($orgUnitId)
    {
        $response = $this->sendGet("accreditors/{$orgUnitId}/accreditations");
        $items = $this->extractListItems($response, 'accreditations');

        return $this->hydrateModels('Accreditation', $items);
    }

    /**
     * Get a specific accreditation
     *
     * @param string $orgUnitId
     * @param string $accreditationId
     * @return EduDexAccreditation
     */
    public function getAccreditation($orgUnitId, $accreditationId)
    {
        $response = $this->sendGet("accreditors/{$orgUnitId}/accreditations/{$accreditationId}");
        return $this->hydrateModel('Accreditation', $response);
    }

    /**
     * Create an accreditation
     *
     * @param string $orgUnitId Accreditor ID
     * @param array $data Accreditation data with orgUnitId (supplier), accreditation type, validFrom, validUntil
     * @return EduDexAccreditation
     */
    public function createAccreditation($orgUnitId, $data)
    {
        $this->validateRequired(
            array_merge(array('orgUnitId' => $orgUnitId), $data),
            array('orgUnitId', 'accreditation', 'validFrom', 'validUntil')
        );

        $response = $this->sendPost("accreditors/{$orgUnitId}/accreditations", $data);
        return $this->hydrateModel('Accreditation', $response);
    }

    /**
     * Update an accreditation
     *
     * @param string $orgUnitId Accreditor ID
     * @param string $accreditationId
     * @param array $data Partial accreditation data to update
     * @return EduDexAccreditation
     */
    public function updateAccreditation($orgUnitId, $accreditationId, $data)
    {
        $this->validateRequired(
            compact('orgUnitId', 'accreditationId'),
            array('orgUnitId', 'accreditationId')
        );

        $response = $this->sendPatch("accreditors/{$orgUnitId}/accreditations/{$accreditationId}", $data);
        return $this->hydrateModel('Accreditation', $response);
    }

    /**
     * Delete an accreditation
     *
     * @param string $orgUnitId
     * @param string $accreditationId
     * @return void
     */
    public function deleteAccreditation($orgUnitId, $accreditationId)
    {
        $this->sendDelete("accreditors/{$orgUnitId}/accreditations/{$accreditationId}");
    }
}
