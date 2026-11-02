<?php

namespace Restruct\EduDex\Tests\Endpoints;

use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Models\DynamicCatalog;
use Restruct\EduDex\Models\Organization;
use Restruct\EduDex\Models\StaticCatalog;
use Restruct\EduDex\Models\Webhook;
use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Organizations endpoint: request building and response handling per the bundled spec
 *
 * Not covered here on purpose, because the current code disagrees with the spec
 * (see https://github.com/restruct/edudex-api-client/issues/3): listCatalogs(),
 * bulkAddPrograms(), bulkRemovePrograms(), addSupplierToDynamicCatalog(), testWebhook().
 * Their tests arrive with the fixes, failing first.
 */
class OrganizationsTest extends ApiTestCase
{
    private const CATALOG_ID = '12345678-1234-1234-1234-123456789012';

    private static function catalog(array $extra = []): array
    {
        return $extra + [
            'catalogId' => self::CATALOG_ID,
            'clientId' => 'client-1',
            'title' => 'Catalog title',
            'countActive' => 3,
            'countTotal' => 5,
        ];
    }

    private static function webhook(array $extra = []): array
    {
        return $extra + [
            'id' => 'hook-1',
            'url' => 'https://example.com/hook',
            'events' => ['catalog'],
            'active' => true,
            'lastStatus' => 200,
        ];
    }

    public function testListHydratesOrganizations(): void
    {
        $client = $this->makeClient([self::json(['organizations' => [
            ['id' => 'org-1', 'name' => ['nl' => 'Een', 'en' => 'One'], 'roles' => ['supplier'], 'vatExempt' => true],
            ['id' => 'org-2', 'name' => ['nl' => 'Twee'], 'roles' => ['client']],
        ]])]);

        $orgs = $client->organizations()->list();

        $this->assertSentRequest('GET', 'organizations');
        $this->assertCount(2, $orgs);
        $this->assertContainsOnlyInstancesOf(Organization::class, $orgs);
        $this->assertSame('org-1', $orgs[0]->id);
        $this->assertSame('One', $orgs[0]->getLocalizedName('en'));
        $this->assertTrue($orgs[0]->isSupplier());
        $this->assertTrue($orgs[0]->vatExempt);
        $this->assertTrue($orgs[1]->isClient());
        $this->assertFalse($orgs[1]->vatExempt);
    }

    public function testListReturnsEmptyArrayWhenKeyIsMissing(): void
    {
        $client = $this->makeClient([self::json([])]);

        $this->assertSame([], $client->organizations()->list());
    }

    public function testGet(): void
    {
        $client = $this->makeClient([self::json(['id' => 'org-1', 'name' => ['nl' => 'Een'], 'roles' => ['accreditor']])]);

        $org = $client->organizations()->get('org-1');

        $this->assertSentRequest('GET', 'organizations/org-1');
        $this->assertInstanceOf(Organization::class, $org);
        $this->assertTrue($org->isAccreditor());
        $this->assertSame('Een', $org->getLocalizedName('nl'));
    }

    public function testListStaticCatalogs(): void
    {
        $client = $this->makeClient([self::json(['catalogs' => [self::catalog()]])]);

        $catalogs = $client->organizations()->listStaticCatalogs('org-1');

        $this->assertSentRequest('GET', 'organizations/org-1/staticcatalogs');
        $this->assertCount(1, $catalogs);
        $this->assertInstanceOf(StaticCatalog::class, $catalogs[0]);
        $this->assertSame(self::CATALOG_ID, $catalogs[0]->catalogId);
        $this->assertSame(3, $catalogs[0]->countActive);
    }

    public function testGetStaticCatalog(): void
    {
        $client = $this->makeClient([self::json(self::catalog())]);

        $catalog = $client->organizations()->getStaticCatalog('org-1', self::CATALOG_ID);

        $this->assertSentRequest('GET', 'organizations/org-1/staticcatalogs/' . self::CATALOG_ID);
        $this->assertSame('Catalog title', $catalog->title);
    }

    public function testCreateStaticCatalog(): void
    {
        $client = $this->makeClient([self::json(self::catalog())]);

        $catalog = $client->organizations()->createStaticCatalog('org-1', 'Catalog title', 'client-1');

        $this->assertSentRequest('POST', 'organizations/org-1/staticcatalogs', [], [
            'title' => 'Catalog title',
            'clientId' => 'client-1',
        ]);
        $this->assertInstanceOf(StaticCatalog::class, $catalog);
    }

    public function testCreateStaticCatalogRejectsEmptyTitleWithoutSending(): void
    {
        $client = $this->makeClient();

        try {
            $client->organizations()->createStaticCatalog('org-1', '', 'client-1');
            $this->fail('Expected an EduDexException');
        } catch (EduDexException $e) {
            $this->assertStringContainsString("'title'", $e->getMessage());
        }
        $this->assertCount(0, $this->history);
    }

    public function testUpdateStaticCatalog(): void
    {
        $client = $this->makeClient([self::json(self::catalog(['title' => 'New']))]);

        $catalog = $client->organizations()->updateStaticCatalog('org-1', self::CATALOG_ID, 'New');

        $this->assertSentRequest('PATCH', 'organizations/org-1/staticcatalogs/' . self::CATALOG_ID, [], ['title' => 'New']);
        $this->assertSame('New', $catalog->title);
    }

    public function testDeleteStaticCatalog(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->organizations()->deleteStaticCatalog('org-1', self::CATALOG_ID);

        $this->assertSentRequest('DELETE', 'organizations/org-1/staticcatalogs/' . self::CATALOG_ID);
    }

    public function testListDynamicCatalogs(): void
    {
        $client = $this->makeClient([self::json(['catalogs' => [self::catalog(['regionFilter' => '1000-1999'])]])]);

        $catalogs = $client->organizations()->listDynamicCatalogs('org-1');

        $this->assertSentRequest('GET', 'organizations/org-1/dynamiccatalogs');
        $this->assertInstanceOf(DynamicCatalog::class, $catalogs[0]);
        $this->assertSame('1000-1999', $catalogs[0]->regionFilter);
    }

    public function testGetDynamicCatalog(): void
    {
        $client = $this->makeClient([self::json(self::catalog())]);

        $catalog = $client->organizations()->getDynamicCatalog('org-1', self::CATALOG_ID);

        $this->assertSentRequest('GET', 'organizations/org-1/dynamiccatalogs/' . self::CATALOG_ID);
        $this->assertInstanceOf(DynamicCatalog::class, $catalog);
        $this->assertNull($catalog->regionFilter);
    }

    public function testCreateDynamicCatalogWithoutRegionFilterOmitsIt(): void
    {
        $client = $this->makeClient([self::json(self::catalog())]);

        $client->organizations()->createDynamicCatalog('org-1', 'Catalog title', 'client-1');

        $this->assertSentRequest('POST', 'organizations/org-1/dynamiccatalogs', [], [
            'title' => 'Catalog title',
            'clientId' => 'client-1',
        ]);
    }

    public function testCreateDynamicCatalogWithRegionFilter(): void
    {
        $client = $this->makeClient([self::json(self::catalog(['regionFilter' => '1000-1999,2345']))]);

        $client->organizations()->createDynamicCatalog('org-1', 'Catalog title', 'client-1', '1000-1999,2345');

        $this->assertSentRequest('POST', 'organizations/org-1/dynamiccatalogs', [], [
            'title' => 'Catalog title',
            'clientId' => 'client-1',
            'regionFilter' => '1000-1999,2345',
        ]);
    }

    public function testUpdateDynamicCatalogSendsOnlyGivenFields(): void
    {
        $client = $this->makeClient([self::json(self::catalog(['regionFilter' => '3000']))]);

        $client->organizations()->updateDynamicCatalog('org-1', self::CATALOG_ID, null, '3000');

        $this->assertSentRequest('PATCH', 'organizations/org-1/dynamiccatalogs/' . self::CATALOG_ID, [], [
            'regionFilter' => '3000',
        ]);
    }

    public function testUpdateDynamicCatalogWithBothFields(): void
    {
        $client = $this->makeClient([self::json(self::catalog())]);

        $client->organizations()->updateDynamicCatalog('org-1', self::CATALOG_ID, 'T', '3000');

        $this->assertSentRequest('PATCH', 'organizations/org-1/dynamiccatalogs/' . self::CATALOG_ID, [], [
            'title' => 'T',
            'regionFilter' => '3000',
        ]);
    }

    public function testDeleteDynamicCatalog(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->organizations()->deleteDynamicCatalog('org-1', self::CATALOG_ID);

        $this->assertSentRequest('DELETE', 'organizations/org-1/dynamiccatalogs/' . self::CATALOG_ID);
    }

    public function testRemoveSupplierFromDynamicCatalog(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->organizations()->removeSupplierFromDynamicCatalog('org-1', self::CATALOG_ID, 'supplier-1');

        $this->assertSentRequest('DELETE', 'organizations/org-1/dynamiccatalogs/' . self::CATALOG_ID . '/suppliers/supplier-1');
    }

    public function testListWebhooks(): void
    {
        $client = $this->makeClient([self::json(['webhooks' => [self::webhook(['lastCalled' => '2024-01-01T01:02:03Z'])]])]);

        $hooks = $client->organizations()->listWebhooks('org-1');

        $this->assertSentRequest('GET', 'organizations/org-1/webhooks');
        $this->assertInstanceOf(Webhook::class, $hooks[0]);
        $this->assertTrue($hooks[0]->active);
        $this->assertSame(200, $hooks[0]->lastStatus);
        $this->assertSame('2024-01-01', $hooks[0]->lastCalled->format('Y-m-d'));
    }

    public function testGetWebhook(): void
    {
        $client = $this->makeClient([self::json(self::webhook())]);

        $hook = $client->organizations()->getWebhook('org-1', 'hook-1');

        $this->assertSentRequest('GET', 'organizations/org-1/webhooks/hook-1');
        $this->assertSame('https://example.com/hook', $hook->url);
    }

    public function testCreateWebhook(): void
    {
        $client = $this->makeClient([self::json(self::webhook())]);

        $hook = $client->organizations()->createWebhook('org-1', 'https://example.com/hook', ['catalog']);

        $this->assertSentRequest('POST', 'organizations/org-1/webhooks', [], [
            'url' => 'https://example.com/hook',
            'events' => ['catalog'],
        ]);
        $this->assertSame('hook-1', $hook->id);
    }

    public function testCreateWebhookRejectsEmptyUrlWithoutSending(): void
    {
        $client = $this->makeClient();

        $this->expectException(EduDexException::class);
        try {
            $client->organizations()->createWebhook('org-1', '', ['catalog']);
        } finally {
            $this->assertCount(0, $this->history);
        }
    }

    public function testUpdateWebhookSendsOnlyGivenFields(): void
    {
        $client = $this->makeClient([self::json(self::webhook(['active' => false]))]);

        $hook = $client->organizations()->updateWebhook('org-1', 'hook-1', null, null, false);

        $this->assertSentRequest('PATCH', 'organizations/org-1/webhooks/hook-1', [], ['active' => false]);
        $this->assertFalse($hook->active);
    }

    public function testUpdateWebhookWithAllFields(): void
    {
        $client = $this->makeClient([self::json(self::webhook())]);

        $client->organizations()->updateWebhook('org-1', 'hook-1', 'https://example.com/new', ['catalog'], true);

        $this->assertSentRequest('PATCH', 'organizations/org-1/webhooks/hook-1', [], [
            'url' => 'https://example.com/new',
            'events' => ['catalog'],
            'active' => true,
        ]);
    }

    public function testDeleteWebhook(): void
    {
        $client = $this->makeClient([self::noContent()]);

        $client->organizations()->deleteWebhook('org-1', 'hook-1');

        $this->assertSentRequest('DELETE', 'organizations/org-1/webhooks/hook-1');
    }
}
