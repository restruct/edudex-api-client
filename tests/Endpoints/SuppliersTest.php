<?php

namespace Restruct\EduDex\Tests\Endpoints;

use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Models\Supplier;
use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Suppliers endpoint: request building and response handling per the bundled spec
 *
 * Not covered here on purpose, because the current code disagrees with the spec:
 * getProgram(), listPrograms() with a client id, the response side of
 * listPrograms()/listDiscounts() and of upsertProgram()
 * (https://github.com/restruct/edudex-api-client/issues/3).
 * Their tests arrive with the fixes, failing first.
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
        # Request side only: the response key is covered with issue #3
        $client = $this->makeClient([self::json(['items' => []])]);

        $client->suppliers()->listPrograms('sup-1');

        $this->assertSentRequest('GET', 'suppliers/sup-1/programs');
    }

    public function testListDiscountsRequest(): void
    {
        # Request side only: the response key is covered with issue #3
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
}
