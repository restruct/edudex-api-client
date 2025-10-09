<?php

/**
 * Programs endpoint
 *
 * Handles bulk program operations
 */
class EduDexPrograms extends EduDexBaseEndpoint
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
        $programs,
        $viewerOrgUnitId = null,
        $viewDiscountsForOrgUnitId = null
    ) {
        $this->validateRequired(compact('programs'), array('programs'));

        $data = array('programs' => $programs);

        $query = $this->buildQuery(array(
            'viewerOrgUnitId' => $viewerOrgUnitId,
            'viewDiscountsForOrgUnitId' => $viewDiscountsForOrgUnitId,
        ));

        return $this->sendPost('programs/bulk', $data, array(), $query);
    }

    /**
     * Get successful programs from bulk response
     *
     * @param array $bulkResponse Response from bulk() method
     * @return array<Program>
     */
    public function getSuccessful($bulkResponse)
    {
        $items = isset($bulkResponse['successful']) ? $bulkResponse['successful'] : array();
        return $this->hydrateModels('Program', $items);
    }

    /**
     * Get failed program identifiers from bulk response
     *
     * @param array $bulkResponse Response from bulk() method
     * @return array Array of failed program info with error messages
     */
    public function getFailed($bulkResponse)
    {
        return isset($bulkResponse['failed']) ? $bulkResponse['failed'] : array();
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
    protected function sendPost($path, $data = array(), $headers = array(), $query = array())
    {
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return parent::sendPost($path, $data, $headers);
    }
}
