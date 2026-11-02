<?php

namespace Restruct\EduDex\Tests\Endpoints;

use Restruct\EduDex\Exceptions\ApiException;
use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Exceptions\ValidationException;
use Restruct\EduDex\Models\Program;
use Restruct\EduDex\Models\Supplier;
use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Suppliers endpoint: request building and response handling per the bundled spec
 *
 * Tests named "...Issue3" are regressions for spec drift fixed under
 * https://github.com/restruct/edudex-api-client/issues/3
 */
class SuppliersTest extends ApiTestCase
{
    public function testListHydratesSuppliers(): void
    {
        $client = $this->makeClient([self::json(['suppliers' => [
            ['id' => 'sup-1', 'name' => ['nl' => 'Leverancier', 'en' => 'Supplier']],
        ]])]);

        $suppliers = $client->suppliers()->list();

        $this->assertSentRequest('GET', 'suppliers');
        $this->assertCount(1, $suppliers);
        $this->assertInstanceOf(Supplier::class, $suppliers[0]);
        $this->assertSame('sup-1', $suppliers[0]->id);
        $this->assertSame('Supplier', $suppliers[0]->getLocalizedName('en'));
    }

    public function testGet(): void
    {
        $client = $this->makeClient([self::json(['id' => 'sup-1', 'name' => ['nl' => 'Leverancier']])]);

        $supplier = $client->suppliers()->get('sup-1');

        $this->assertSentRequest('GET', 'suppliers/sup-1');
        $this->assertSame('Leverancier', $supplier->getLocalizedName('nl'));
    }

    public function testGetMetadataReturnsRawArray(): void
    {
        $metadata = ['lastEdited' => '2024-01-01T00:00:00Z', 'instituteName' => ['nl' => 'Instituut']];
        $client = $this->makeClient([self::json($metadata)]);

        $this->assertSame($metadata, $client->suppliers()->getMetadata('sup-1'));
        $this->assertSentRequest('GET', 'suppliers/sup-1/metadata');
    }

    public function testUpdateMetadataSendsMetadataAsBody(): void
    {
        $metadata = ['format' => 'v1', 'instituteName' => ['nl' => 'Instituut']];
        $status = ['status' => 'ok', 'validationMessages' => [], 'lastChange' => '2024-01-01T00:00:00Z'];
        $client = $this->makeClient([self::json($status)]);

        $result = $client->suppliers()->updateMetadata('sup-1', $metadata);

        $this->assertSentRequest('PUT', 'suppliers/sup-1/metadata', [], $metadata);
        $this->assertSame($status, $result);
    }

    public function testUpdateMetadataRejectsEmptyMetadataWithoutSending(): void
    {
        $client = $this->makeClient();

        $this->expectException(EduDexException::class);
        try {
            $client->suppliers()->updateMetadata('', ['format' => 'v1']);
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    public function testListProgramsRequestWithoutClientFilter(): void
    {
        $client = $this->makeClient([self::json(['items' => []])]);

        $client->suppliers()->listPrograms('sup-1');

        $this->assertSentRequest('GET', 'suppliers/sup-1/programs');
    }

    public function testListDiscountsRequest(): void
    {
        $client = $this->makeClient([self::json(['items' => []])]);

        $client->suppliers()->listDiscounts('sup-1');

        $this->assertSentRequest('GET', 'suppliers/sup-1/discounts');
    }

    public function testGetDiscountsReturnsRawArray(): void
    {
        $discounts = ['discounts' => [['programId' => 'p1', 'percentage' => 10]]];
        $client = $this->makeClient([self::json($discounts)]);

        $this->assertSame($discounts, $client->suppliers()->getDiscounts('sup-1', 'client-1'));
        $this->assertSentRequest('GET', 'suppliers/sup-1/discounts/client-1');
    }

    public function testUpsertDiscountsSendsDiscountsAsBody(): void
    {
        $discounts = ['format' => 'v1', 'discounts' => [['programId' => 'p1']]];
        $status = ['status' => 'ok', 'validationMessages' => [], 'lastChange' => '2024-01-01T00:00:00Z'];
        $client = $this->makeClient([self::json($status, 201)]);

        $result = $client->suppliers()->upsertDiscounts('sup-1', 'client-1', $discounts);

        $this->assertSentRequest('PUT', 'suppliers/sup-1/discounts/client-1', [], $discounts);
        $this->assertSame($status, $result);
    }

    public function testUpsertDiscountsRejectsMissingClientWithoutSending(): void
    {
        $client = $this->makeClient();

        $this->expectException(EduDexException::class);
        try {
            $client->suppliers()->upsertDiscounts('sup-1', '', ['format' => 'v1']);
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    public function testDeleteDiscounts(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->suppliers()->deleteDiscounts('sup-1', 'client-1');

        $this->assertSentRequest('DELETE', 'suppliers/sup-1/discounts/client-1');
    }

    /**
     * A program body with every field the spec's program_in schema requires
     *
     * @return array
     */
    private static function programData(): array
    {
        return [
            'editor' => 'Editor',
            'format' => 'v1',
            'generator' => 'unit-test',
            'lastEdited' => '2024-01-01T00:00:00Z',
            'programAdmission' => [],
            'programClassification' => [],
            'programContacts' => [],
            'programCurriculum' => [],
            'programDescriptions' => ['title' => ['nl' => 'Cursus']],
            'programSchedule' => [],
        ];
    }

    /**
     * Regression for issue #1: the spec defines PUT only on .../programs/{programId}/{clientId}
     */
    public function testUpsertProgramPutsToThePathWithTheClientId(): void
    {
        $client = $this->makeClient([self::json(['status' => 'ok', 'validationMessages' => [], 'lastChange' => '2024-01-01T00:00:00Z'])]);

        $client->suppliers()->upsertProgram('sup-1', 'prog-1', 'client-1', self::programData());

        $this->assertSentRequest('PUT', 'suppliers/sup-1/programs/prog-1/client-1', [], self::programData());
    }

    /**
     * Regression for issue #1: "public" is a valid client id segment (clientIdOrPublic)
     */
    public function testUpsertProgramForThePublicCatalog(): void
    {
        $client = $this->makeClient([self::json(['status' => 'ok', 'validationMessages' => [], 'lastChange' => '2024-01-01T00:00:00Z'])]);

        $client->suppliers()->upsertProgram('sup-1', 'prog-1', 'public', self::programData());

        $this->assertSentRequest('PUT', 'suppliers/sup-1/programs/prog-1/public', [], self::programData());
    }

    public function testUpsertProgramRejectsEmptyClientIdWithoutSending(): void
    {
        $client = $this->makeClient();

        $this->expectException(EduDexException::class);
        try {
            $client->suppliers()->upsertProgram('sup-1', 'prog-1', '', self::programData());
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    /**
     * Regression for issue #1: the spec defines DELETE only on .../programs/{programId}/{clientId}
     */
    public function testDeleteProgramDeletesThePathWithTheClientId(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->suppliers()->deleteProgram('sup-1', 'prog-1', 'client-1');

        $this->assertSentRequest('DELETE', 'suppliers/sup-1/programs/prog-1/client-1');
    }

    public function testDeleteProgramRejectsEmptyClientIdWithoutSending(): void
    {
        $client = $this->makeClient();

        $this->expectException(EduDexException::class);
        try {
            $client->suppliers()->deleteProgram('sup-1', 'prog-1', '');
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    /**
     * Program list items as the spec's suppliers_programs_list_response has them
     *
     * @return array
     */
    private static function programItems(): array
    {
        return [
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'public', 'lastChange' => '2024-01-01T00:00:00Z'],
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-2', 'clientId' => 'client-1', 'lastChange' => '2024-01-02T00:00:00Z'],
        ];
    }

    /**
     * Issue #3: the list is under "items", not "programs"
     */
    public function testListProgramsReturnsTheItemsIssue3(): void
    {
        $client = $this->makeClient([self::json(['items' => self::programItems()])]);

        $this->assertSame(self::programItems(), $client->suppliers()->listPrograms('sup-1'));
    }

    /**
     * Issue #3: the spec has no clientId query parameter; the filter is applied client-side
     */
    public function testListProgramsFiltersOnClientIdClientSideIssue3(): void
    {
        $client = $this->makeClient([self::json(['items' => self::programItems()])]);

        $programs = $client->suppliers()->listPrograms('sup-1', 'client-1');

        $this->assertSentRequest('GET', 'suppliers/sup-1/programs');
        $this->assertSame([self::programItems()[1]], $programs);
    }

    /**
     * Issue #3: the list is under "items", not "clients"
     */
    public function testListDiscountsReturnsTheItemsIssue3(): void
    {
        $items = [['clientId' => 'client-1', 'lastChange' => '2024-01-01T00:00:00Z']];
        $client = $this->makeClient([self::json(['items' => $items])]);

        $this->assertSame($items, $client->suppliers()->listDiscounts('sup-1'));
    }

    /**
     * Issue #3: GET .../programs/{programId} lists the clients a program is published for
     */
    public function testListProgramClientsIssue3(): void
    {
        $items = [self::programItems()[0]];
        $client = $this->makeClient([self::json(['items' => $items])]);

        $this->assertSame($items, $client->suppliers()->listProgramClients('sup-1', 'prog-1'));
        $this->assertSentRequest('GET', 'suppliers/sup-1/programs/prog-1');
    }

    /**
     * Issue #3: one program's data is only available through POST /programs/bulk;
     * getProgram() used to GET the client list and hydrate that into a Program
     */
    public function testGetProgramFetchesThroughBulkIssue3(): void
    {
        $data = self::programData();
        $client = $this->makeClient([self::json(['programs' => [
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'client-1', 'error' => null, 'data' => $data],
        ]])]);

        $program = $client->suppliers()->getProgram('sup-1', 'prog-1', 'client-1');

        $this->assertSentRequest('POST', 'programs/bulk', [], [
            'programs' => [['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'client-1']],
        ]);
        $this->assertInstanceOf(Program::class, $program);
        $this->assertSame('sup-1', $program->orgUnitId);
        $this->assertSame('prog-1', $program->programId);
        $this->assertSame('client-1', $program->clientId);
        $this->assertSame($data, $program->programData);
        $this->assertSame('Cursus', $program->getTitle('nl'));
    }

    public function testGetProgramThrowsTheApiErrorForAFailedItemIssue3(): void
    {
        $client = $this->makeClient([self::json(['programs' => [
            ['orgUnitId' => 'sup-1', 'programId' => 'prog-1', 'clientId' => 'client-1', 'error' => 'Program not found', 'data' => null],
        ]])]);

        try {
            $client->suppliers()->getProgram('sup-1', 'prog-1', 'client-1');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertStringContainsString('Program not found', $e->getMessage());
            $this->assertSame('prog-1', $e->getContextValue('programId'));
        }
    }

    /**
     * Issue #3: the PUT response is a status object, not a program. upsertProgram()
     * used to hydrate that into a Program whose typed properties were never set
     */
    public function testUpsertProgramReturnsTheSubmittedProgramIssue3(): void
    {
        $client = $this->makeClient([self::json(['status' => 'ok', 'validationMessages' => [], 'lastChange' => '2024-01-01T00:00:00Z'])]);

        $program = $client->suppliers()->upsertProgram('sup-1', 'prog-1', 'client-1', self::programData());

        $this->assertSame('sup-1', $program->orgUnitId);
        $this->assertSame('prog-1', $program->programId);
        $this->assertSame('client-1', $program->clientId);
        $this->assertSame('v1', $program->format);
        $this->assertSame(self::programData(), $program->programData);
    }

    /**
     * Issue #3: the status (and its validation messages) is available through a new method
     */
    public function testUpsertProgramWithStatusReturnsTheApiStatusIssue3(): void
    {
        $status = ['status' => 'unchanged', 'validationMessages' => [], 'lastChange' => '2024-01-01T00:00:00Z'];
        $client = $this->makeClient([self::json($status)]);

        $this->assertSame($status, $client->suppliers()->upsertProgramWithStatus('sup-1', 'prog-1', 'client-1', self::programData()));
        $this->assertSentRequest('PUT', 'suppliers/sup-1/programs/prog-1/client-1', [], self::programData());
    }

    /**
     * Issue #3: a 400 on the PUT carries validationMessages (put_badrequest)
     */
    public function testUpsertProgramRejectedWithValidationMessagesThrowsValidationExceptionIssue3(): void
    {
        $client = $this->makeClient([self::json([
            'status' => 400,
            'error' => 'Validation failed',
            'validationMessages' => [['level' => 'error', 'message' => 'Title missing', 'ruleName' => 'r1', 'forClient' => null]],
        ], 400)]);

        try {
            $client->suppliers()->upsertProgram('sup-1', 'prog-1', 'client-1', self::programData());
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['Title missing'], $e->getErrorMessages());
        }
    }
}
