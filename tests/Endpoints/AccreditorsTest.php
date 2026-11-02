<?php

namespace Restruct\EduDex\Tests\Endpoints;

use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Models\Accreditation;
use Restruct\EduDex\Models\Accreditor;
use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Accreditors endpoint: request building and response handling per the bundled spec
 */
class AccreditorsTest extends ApiTestCase
{
    private const ACCREDITATION_ID = '12345678-1234-1234-1234-123456789012';

    private static function accreditation(array $extra = []): array
    {
        return $extra + [
            'accreditationId' => self::ACCREDITATION_ID,
            'orgUnitId' => 'sup-1',
            'accreditation' => 'nrto',
            'validFrom' => '2024-01-01',
            'validUntil' => '2025-12-31',
        ];
    }

    public function testListHydratesAccreditors(): void
    {
        $client = $this->makeClient([self::json(['accreditors' => [
            ['id' => 'acc-1', 'name' => ['nl' => 'Keurmerk']],
        ]])]);

        $accreditors = $client->accreditors()->list();

        $this->assertSentRequest('GET', 'accreditors');
        $this->assertInstanceOf(Accreditor::class, $accreditors[0]);
        $this->assertSame('acc-1', $accreditors[0]->id);
    }

    public function testGet(): void
    {
        $client = $this->makeClient([self::json(['id' => 'acc-1', 'name' => ['nl' => 'Keurmerk']])]);

        $accreditor = $client->accreditors()->get('acc-1');

        $this->assertSentRequest('GET', 'accreditors/acc-1');
        $this->assertInstanceOf(Accreditor::class, $accreditor);
    }

    public function testListAccreditations(): void
    {
        $client = $this->makeClient([self::json(['accreditations' => [self::accreditation()]])]);

        $list = $client->accreditors()->listAccreditations('acc-1');

        $this->assertSentRequest('GET', 'accreditors/acc-1/accreditations');
        $this->assertInstanceOf(Accreditation::class, $list[0]);
        $this->assertSame('nrto', $list[0]->accreditation);
        $this->assertSame('2025-12-31', $list[0]->validUntil->format('Y-m-d'));
    }

    public function testGetAccreditation(): void
    {
        $client = $this->makeClient([self::json(self::accreditation())]);

        $item = $client->accreditors()->getAccreditation('acc-1', self::ACCREDITATION_ID);

        $this->assertSentRequest('GET', 'accreditors/acc-1/accreditations/' . self::ACCREDITATION_ID);
        $this->assertSame('sup-1', $item->orgUnitId);
    }

    public function testCreateAccreditation(): void
    {
        $data = [
            'orgUnitId' => 'sup-1',
            'accreditation' => 'nrto',
            'validFrom' => '2024-01-01',
            'validUntil' => '2025-12-31',
        ];
        $client = $this->makeClient([self::json(self::accreditation())]);

        $item = $client->accreditors()->createAccreditation('acc-1', $data);

        $this->assertSentRequest('POST', 'accreditors/acc-1/accreditations', [], $data);
        $this->assertSame(self::ACCREDITATION_ID, $item->accreditationId);
    }

    public function testCreateAccreditationRejectsMissingFieldsWithoutSending(): void
    {
        $client = $this->makeClient();

        try {
            $client->accreditors()->createAccreditation('acc-1', ['accreditation' => 'nrto', 'validFrom' => '2024-01-01']);
            $this->fail('Expected an EduDexException');
        } catch (EduDexException $e) {
            $this->assertStringContainsString("'validUntil'", $e->getMessage());
        }
        $this->assertCount(0, $this->history);
    }

    public function testUpdateAccreditation(): void
    {
        $client = $this->makeClient([self::json(self::accreditation(['validUntil' => '2026-12-31']))]);

        $item = $client->accreditors()->updateAccreditation('acc-1', self::ACCREDITATION_ID, ['validUntil' => '2026-12-31']);

        $this->assertSentRequest('PATCH', 'accreditors/acc-1/accreditations/' . self::ACCREDITATION_ID, [], ['validUntil' => '2026-12-31']);
        $this->assertSame('2026-12-31', $item->validUntil->format('Y-m-d'));
    }

    public function testDeleteAccreditation(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->accreditors()->deleteAccreditation('acc-1', self::ACCREDITATION_ID);

        $this->assertSentRequest('DELETE', 'accreditors/acc-1/accreditations/' . self::ACCREDITATION_ID);
    }
}
