<?php

namespace Restruct\EduDex\Tests\Endpoints;

use PHPUnit\Framework\Attributes\DataProvider;
use Restruct\EduDex\Models\Program;
use Restruct\EduDex\Models\ValidationResult;
use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Programs (bulk) and Validations endpoints: request building per the bundled spec
 *
 * Tests named "...Issue3" are regressions for spec drift fixed under
 * https://github.com/restruct/edudex-api-client/issues/3
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

    /**
     * A bulk response as the spec's programs_bulk_response has it
     *
     * @return array
     */
    private static function bulkResponse(): array
    {
        return ['programs' => [
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'public', 'error' => null, 'data' => [
                'format' => 'v1',
                'editor' => 'Editor',
                'programDescriptions' => ['title' => ['nl' => 'Cursus']],
            ]],
            ['orgUnitId' => 'sup-2', 'programId' => 'prog-2', 'clientId' => 'client-1', 'error' => 'Not found', 'data' => null],
        ]];
    }

    /**
     * Issue #3: getSuccessful() used to read a "successful" key the spec does not have
     */
    public function testGetSuccessfulHydratesProgramsWithDataIssue3(): void
    {
        $client = $this->makeClient();

        $programs = $client->programs()->getSuccessful(self::bulkResponse());

        $this->assertCount(1, $programs);
        $this->assertInstanceOf(Program::class, $programs[0]);
        $this->assertSame('sup-1', $programs[0]->orgUnitId);
        $this->assertSame('prog-1', $programs[0]->programId);
        $this->assertSame('public', $programs[0]->clientId);
        $this->assertSame('v1', $programs[0]->format);
        $this->assertSame('Cursus', $programs[0]->getTitle('nl'));
    }

    /**
     * Issue #3: getFailed() used to read a "failed" key the spec does not have
     */
    public function testGetFailedReturnsTheItemsWithAnErrorIssue3(): void
    {
        $client = $this->makeClient();

        $failed = $client->programs()->getFailed(self::bulkResponse());

        $this->assertSame([self::bulkResponse()['programs'][1]], $failed);
    }

    /**
     * Validation messages as the spec's validationMessages has them
     *
     * @return array
     */
    private static function specMessages(): array
    {
        return [
            ['level' => 'error', 'message' => 'Title missing', 'ruleName' => 'title-required', 'contextPath' => 'programDescriptions.title', 'forClient' => null],
            ['level' => 'warning', 'message' => 'Short description', 'ruleName' => 'description-length', 'forClient' => 'client-1'],
        ];
    }

    /**
     * Issue #3: ValidationResult used to read "messages"/"severity" only, so a
     * spec-shaped response with errors came out valid
     */
    public function testSpecShapedInvalidResponseIsNotValidIssue3(): void
    {
        $client = $this->makeClient([self::json(['validates' => false, 'validationMessages' => self::specMessages()])]);

        $result = $client->validations()->validateProgram(['format' => 'v1']);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
        $this->assertSame(['Title missing'], $result->getErrorMessages());
        $this->assertSame(['Short description'], $result->getWarningMessages());
        $error = array_values($result->getErrors())[0];
        $this->assertSame('programDescriptions.title', $error->contextPath);
        $this->assertSame('title-required', $error->code);
    }

    /**
     * Issue #3: "validates" is the API's verdict, even without messages
     */
    public function testValidatesFalseWithoutMessagesIsNotValidIssue3(): void
    {
        $client = $this->makeClient([self::json(['validates' => false, 'validationMessages' => []])]);

        $this->assertFalse($client->validations()->validateInstitute(['x' => 1])->isValid());
    }

    public function testWarningsOnlyResponseIsValidIssue3(): void
    {
        $client = $this->makeClient([self::json(['validates' => true, 'validationMessages' => [self::specMessages()[1]]])]);

        $result = $client->validations()->validateDiscounts(['x' => 1]);

        $this->assertTrue($result->isValid());
        $this->assertSame(1, $result->getWarningCount());
        $this->assertSame(0, $result->getErrorCount());
    }
}
