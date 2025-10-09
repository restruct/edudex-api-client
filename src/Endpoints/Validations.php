<?php

namespace Restruct\EduDex\Endpoints;

use Restruct\EduDex\Models\ValidationResult;

/**
 * Validations endpoint
 *
 * Validates program data, institute metadata, and discounts before submission
 *
 * @package Restruct\EduDex\Endpoints
 */
class Validations extends BaseEndpoint
{
    /**
     * Validate program data
     *
     * @param array $programData Full program data structure
     * @return ValidationResult
     */
    public function validateProgram(array $programData): ValidationResult
    {
        $data = $this->wrapInDataKey($programData);
        $response = $this->sendPost('validations/programs', $data);
        return $this->hydrateModel(ValidationResult::class, $response);
    }

    /**
     * Validate institute metadata
     *
     * @param array $instituteData Institute metadata structure
     * @return ValidationResult
     */
    public function validateInstitute(array $instituteData): ValidationResult
    {
        $data = $this->wrapInDataKey($instituteData);
        $response = $this->sendPost('validations/institutes', $data);
        return $this->hydrateModel(ValidationResult::class, $response);
    }

    /**
     * Validate discount data
     *
     * @param array $discountData Discount data structure
     * @return ValidationResult
     */
    public function validateDiscounts(array $discountData): ValidationResult
    {
        $data = $this->wrapInDataKey($discountData);
        $response = $this->sendPost('validations/discounts', $data);
        return $this->hydrateModel(ValidationResult::class, $response);
    }

    /**
     * Check if validation result has errors
     *
     * @param ValidationResult $result
     * @return bool
     */
    public function hasErrors(ValidationResult $result): bool
    {
        return $result->hasErrors();
    }

    /**
     * Get error messages from validation result
     *
     * @param ValidationResult $result
     * @return array
     */
    public function getErrors(ValidationResult $result): array
    {
        return $result->getErrors();
    }

    /**
     * Get warning messages from validation result
     *
     * @param ValidationResult $result
     * @return array
     */
    public function getWarnings(ValidationResult $result): array
    {
        return $result->getWarnings();
    }

    /**
     * Check if validation passed (no errors)
     *
     * @param ValidationResult $result
     * @return bool
     */
    public function isValid(ValidationResult $result): bool
    {
        return $result->isValid();
    }

    /**
     * Wrap data in 'data' key if not already wrapped
     *
     * @param array $data
     * @return array
     */
    private function wrapInDataKey(array $data): array
    {
        if (isset($data['data'])) {
            return $data;
        }

        return ['data' => $data];
    }
}
