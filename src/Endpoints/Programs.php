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
     * @param string|null $viewerOrgUnitId View programs as this organization (sent as "viewer"; the API requires a special token for it)
     * @param string|null $viewDiscountsForOrgUnitId Apply discounts for this client (sent as "viewDiscountsFor")
     * @return array Response with 'successful' and 'failed' arrays
     */
    public function bulk(
        array $programs,
        ?string $viewerOrgUnitId = null,
        ?string $viewDiscountsForOrgUnitId = null
    ): array {
        $this->validateRequired(compact('programs'), ['programs']);

        # The spec's programs_bulk_requestbody carries the viewer options as body fields.
        # They used to be built as a query array and passed as a 4th argument sendPost()
        # does not take, so PHP dropped them (#1). Null values are left out.
        $data = array_merge(['programs' => $programs], $this->buildQuery([
            'viewer' => $viewerOrgUnitId,
            'viewDiscountsFor' => $viewDiscountsForOrgUnitId,
        ]));

        return $this->sendPost('programs/bulk', $data);
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
}
