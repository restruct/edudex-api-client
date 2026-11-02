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
     * @return array Response with a "programs" list; each item has "data" or "error" (see getSuccessful()/getFailed())
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
     * The spec's response lists every requested program under "programs", each with
     * either "data" or an "error". The old "successful" key is still read as a fallback.
     *
     * @param array $bulkResponse Response from bulk() method
     * @return array<Program>
     */
    public function getSuccessful(array $bulkResponse): array
    {
        if (!isset($bulkResponse['programs'])) {
            $items = $bulkResponse['successful'] ?? [];
            return $this->hydrateModels(Program::class, $items);
        }

        $programs = [];
        foreach ($bulkResponse['programs'] as $item) {
            if (($item['error'] ?? null) === null && is_array($item['data'] ?? null)) {
                $programs[] = static::hydrateBulkItem($item);
            }
        }

        return $programs;
    }

    /**
     * Get failed program identifiers from bulk response
     *
     * @param array $bulkResponse Response from bulk() method
     * @return array Array of failed program info with error messages (orgUnitId, programId, clientId, error)
     */
    public function getFailed(array $bulkResponse): array
    {
        if (!isset($bulkResponse['programs'])) {
            return $bulkResponse['failed'] ?? [];
        }

        return array_values(array_filter(
            $bulkResponse['programs'],
            fn($item) => ($item['error'] ?? null) !== null
        ));
    }

    /**
     * Build a Program from one successful item of a bulk response
     *
     * The program fields (format, editor, lastEdited, ...) come from "data", which is
     * also kept whole as programData; the identifiers come from the item itself.
     *
     * @param array $item
     * @return Program
     */
    public static function hydrateBulkItem(array $item): Program
    {
        $data = $item['data'] ?? [];

        # Missing identifiers are left out rather than set to null: the properties are typed string
        $ids = array_filter([
            'orgUnitId' => $item['orgUnitId'] ?? null,
            'programId' => $item['programId'] ?? null,
            'clientId' => $item['clientId'] ?? null,
        ], fn($value) => $value !== null);

        return Program::fromArray(array_merge($data, $ids, ['programData' => $data]));
    }
}
