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
        $response = $this->post('validations/programs', $programData);
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
        $response = $this->post('validations/institutes', $instituteData);
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
        $response = $this->post('validations/discounts', $discountData);
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
}
