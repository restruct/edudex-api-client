<?php

/**
 * Validations endpoint
 *
 * Validates program data, institute metadata, and discounts before submission
 */
class EduDexValidations extends EduDexBaseEndpoint
{
    /**
     * Validate program data
     *
     * @param array $programData Full program data structure
     * @return EduDexValidationResult
     */
    public function validateProgram($programData)
    {
        $data = $this->wrapInDataKey($programData);
        $response = $this->sendPost('validations/programs', [ 'data' => $data]);
        return $this->hydrateModel('EduDexValidationResult', $response);
    }

    /**
     * Validate institute metadata
     *
     * @param array $instituteData Institute metadata structure
     * @return EduDexValidationResult
     */
    public function validateInstitute($instituteData)
    {
        $data = $this->wrapInDataKey($instituteData);
        $response = $this->sendPost('validations/institutes', $data);
        return $this->hydrateModel('EduDexValidationResult', $response);
    }

    /**
     * Validate discount data
     *
     * @param array $discountData Discount data structure
     * @return EduDexValidationResult
     */
    public function validateDiscounts($discountData)
    {
        $data = $this->wrapInDataKey($discountData);
        $response = $this->sendPost('validations/discounts', $data);
        return $this->hydrateModel('EduDexValidationResult', $response);
    }

    /**
     * Check if validation result has errors
     *
     * @param EduDexValidationResult $result
     * @return bool
     */
    public function hasErrors($result)
    {
        return $result->hasErrors();
    }

    /**
     * Get error messages from validation result
     *
     * @param EduDexValidationResult $result
     * @return array
     */
    public function getErrors($result)
    {
        return $result->getErrors();
    }

    /**
     * Get warning messages from validation result
     *
     * @param EduDexValidationResult $result
     * @return array
     */
    public function getWarnings($result)
    {
        return $result->getWarnings();
    }

    /**
     * Check if validation passed (no errors)
     *
     * @param EduDexValidationResult $result
     * @return bool
     */
    public function isValid($result)
    {
        return $result->isValid();
    }

    /**
     * Wrap data in 'data' key if not already wrapped
     *
     * @param array $data
     * @return array
     */
    private function wrapInDataKey($data)
    {
        if (isset($data['data'])) {
            return $data;
        }

        return ['data' => $data];
    }
}
