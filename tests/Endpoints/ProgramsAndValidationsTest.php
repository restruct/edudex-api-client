<?php

namespace Restruct\EduDex\Tests\Endpoints;

use PHPUnit\Framework\Attributes\DataProvider;
use Restruct\EduDex\Models\ValidationResult;
use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Programs (bulk) and Validations endpoints: request building per the bundled spec
 *
 * Not covered here on purpose, because the current code disagrees with the spec:
 * getSuccessful()/getFailed() and ValidationResult reading a spec-shaped
 * response (https://github.com/restruct/edudex-api-client/issues/3).
 * Their tests arrive with the fixes, failing first.
 */
class ProgramsAndValidationsTest extends ApiTestCase
{
    public function testBulkSendsProgramIdentifiersAndReturnsRawResponse(): void
    {
        $programs = [
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'public'],
            ['orgUnitId' => 'sup-2', 'programId' => 'prog-2', 'clientId' => 'client-1'],
        ];
        $response = ['programs' => [
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'public', 'error' => null, 'data' => ['format' => 'v1']],
            ['orgUnitId' => 'sup-2', 'programId' => 'prog-2', 'clientId' => 'client-1', 'error' => 'Not found', 'data' => null],
        ]];
        $client = $this->makeClient([self::json($response)]);

        $result = $client->programs()->bulk($programs);

        $this->assertSentRequest('POST', 'programs/bulk', [], ['programs' => $programs]);
        $this->assertSame($response, $result);
    }

    /**
     * Regression for issue #1: the viewer arguments were dropped. The spec's
     * programs_bulk_requestbody carries them as body fields "viewer" and "viewDiscountsFor"
     */
    public function testBulkSendsViewerArgumentsInTheBody(): void
    {
        $programs = [['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'public']];
        $client = $this->makeClient([self::json(['programs' => []])]);

        $client->programs()->bulk($programs, 'viewer-org', 'discount-org');

        $this->assertSentRequest('POST', 'programs/bulk', [], [
            'programs' => $programs,
            'viewer' => 'viewer-org',
            'viewDiscountsFor' => 'discount-org',
        ]);
    }

    /**
     * Regression for issue #1: each viewer argument is sent on its own when the other is null
     */
    public function testBulkSendsOnlyTheViewerArgumentsGiven(): void
    {
        $programs = [['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'client-1']];
        $client = $this->makeClient([self::json(['programs' => []])]);

        $client->programs()->bulk($programs, null, 'discount-org');

        $this->assertSentRequest('POST', 'programs/bulk', [], [
            'programs' => $programs,
            'viewDiscountsFor' => 'discount-org',
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validationEndpoints(): array
    {
        return [
            'program' => ['validateProgram', 'validations/programs'],
            'institute' => ['validateInstitute', 'validations/institutes'],
            'discounts' => ['validateDiscounts', 'validations/discounts'],
        ];
    }

    #[DataProvider('validationEndpoints')]
    public function testValidateWrapsPayloadInDataKey(string $method, string $path): void
    {
        $client = $this->makeClient([self::json(['validates' => true, 'validationMessages' => []])]);

        $result = $client->validations()->{$method}(['format' => 'v1', 'title' => 'x']);

        $this->assertSentRequest('POST', $path, [], ['data' => ['format' => 'v1', 'title' => 'x']]);
        $this->assertInstanceOf(ValidationResult::class, $result);
    }

    #[DataProvider('validationEndpoints')]
    public function testValidateDoesNotDoubleWrapAlreadyWrappedPayload(string $method, string $path): void
    {
        $client = $this->makeClient([self::json(['validates' => true, 'validationMessages' => []])]);

        $client->validations()->{$method}(['data' => ['format' => 'v1']]);

        $this->assertSentRequest('POST', $path, [], ['data' => ['format' => 'v1']]);
    }
}
