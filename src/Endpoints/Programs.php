<?php

namespace Restruct\EduDex\Endpoints;

use Restruct\EduDex\Models\Program;

/**
 * Programs endpoint
 *
 * Handles bulk program operations
 *
 * @package Restruct\EduDex\Endpoints
 */
class Programs extends BaseEndpoint
{
    /**
     * Get multiple programs in a single request
     *
     * @param array $programs Array of program identifiers: [['orgUnitId' => '...', 'programId' => '...', 'clientId' => '...'], ...]
     * @param string|null $viewerOrgUnitId Organization viewing the data
     * @param string|null $viewDiscountsForOrgUnitId Organization to view discounts for
     * @return array Response with 'successful' and 'failed' arrays
     */
    public function bulk(
        array $programs,
        ?string $viewerOrgUnitId = null,
        ?string $viewDiscountsForOrgUnitId = null
    ): array {
        $this->validateRequired(compact('programs'), ['programs']);

        $data = ['programs' => $programs];

        $query = $this->buildQuery([
            'viewerOrgUnitId' => $viewerOrgUnitId,
            'viewDiscountsForOrgUnitId' => $viewDiscountsForOrgUnitId,
        ]);

        return $this->sendPost('programs/bulk', $data, [], $query);
    }

    /**
     * Get successful programs from bulk response
     *
     * @param array $bulkResponse Response from bulk() method
     * @return array<Program>
     */
    public function getSuccessful(array $bulkResponse): array
    {
        $items = $bulkResponse['successful'] ?? [];
        return $this->hydrateModels(Program::class, $items);
    }

    /**
     * Get failed program identifiers from bulk response
     *
     * @param array $bulkResponse Response from bulk() method
     * @return array Array of failed program info with error messages
     */
    public function getFailed(array $bulkResponse): array
    {
        return $bulkResponse['failed'] ?? [];
    }

    /**
     * Helper method to modify POST request to support query params
     *
     * @param string $path
     * @param array $data
     * @param array $headers
     * @param array $query
     * @return array
     */
    protected function post(string $path, array $data = [], array $headers = [], array $query = []): array
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return parent::post($path, $data, $headers);
    }
}
