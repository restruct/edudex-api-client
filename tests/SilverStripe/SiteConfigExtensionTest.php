<?php

namespace Restruct\EduDex\Tests\SilverStripe;

use Restruct\EduDex\Client;
use Restruct\EduDex\Http\GuzzleClient;
use Restruct\EduDex\Integration\SilverStripe\Extensions\SiteConfigExtension;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * SiteConfigExtension: schema, token/base URL resolution, getEduDexClient(), CMS fields
 *
 * The "token present" branch of the connection status field is not tested: it calls
 * the live API (issue #4 proposes replacing it with an explicit test button).
 *
 * Needs a booted Silverstripe 5 app with a database (CI runs it in a host project);
 * excluded from the standalone suite in phpunit.xml.dist. Written for PHPUnit 9.
 */
class SiteConfigExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * Environment variables before the test
     *
     * @var array
     */
    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->envBackup = Environment::getVariables();
        Environment::setEnv('EDUDEX_API_TOKEN', '');
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);
        parent::tearDown();
    }

    public function testExtensionIsAppliedWithItsFields(): void
    {
        $this->assertTrue(SiteConfig::has_extension(SiteConfigExtension::class));

        $schema = DataObject::getSchema();
        $this->assertSame('Varchar(500)', $schema->fieldSpec(SiteConfig::class, 'EduDexBearerToken', DataObjectSchema::DB_ONLY));
        $this->assertSame('Varchar(255)', $schema->fieldSpec(SiteConfig::class, 'EduDexApiBaseUrl', DataObjectSchema::DB_ONLY));
    }

    public function testDefaultBaseUrlIsSetOnNewRecords(): void
    {
        $config = SiteConfig::create();

        $this->assertSame('https://api.edudex.nl/data/v1/', $config->EduDexApiBaseUrl);
    }

    public function testStoredTokenWinsOverEnvironment(): void
    {
        Environment::setEnv('EDUDEX_API_TOKEN', 'env-token');
        $config = SiteConfig::create(['EduDexBearerToken' => 'db-token']);

        $this->assertSame('db-token', $config->getEduDexToken());
    }

    public function testTokenFallsBackToEnvironment(): void
    {
        Environment::setEnv('EDUDEX_API_TOKEN', 'env-token');
        $config = SiteConfig::create();

        $this->assertSame('env-token', $config->getEduDexToken());
    }

    public function testNoTokenGivesNullTokenAndNullClient(): void
    {
        $config = SiteConfig::create();

        $this->assertNull($config->getEduDexToken());
        $this->assertNull($config->getEduDexClient());
    }

    public function testBaseUrlFallsBackToDefaultWhenEmpty(): void
    {
        $config = SiteConfig::create(['EduDexApiBaseUrl' => '']);
        $this->assertSame('https://api.edudex.nl/data/v1/', $config->getEduDexBaseUrl());

        $config->EduDexApiBaseUrl = 'https://staging.example.test/v1/';
        $this->assertSame('https://staging.example.test/v1/', $config->getEduDexBaseUrl());
    }

    public function testGetEduDexClientUsesStoredTokenAndBaseUrl(): void
    {
        $config = SiteConfig::create([
            'EduDexBearerToken' => 'db-token',
            'EduDexApiBaseUrl' => 'https://staging.example.test/v1/',
        ]);

        $client = $config->getEduDexClient();

        $this->assertInstanceOf(Client::class, $client);
        $http = $client->getHttpClient();
        $this->assertInstanceOf(GuzzleClient::class, $http);
        $this->assertSame('https://staging.example.test/v1', $http->getBaseUrl());
        $this->assertSame('Bearer db-token', $http->getGuzzleClient()->getConfig('headers')['Authorization']);
    }

    public function testCmsFieldsAreHiddenFromNonAdmins(): void
    {
        $this->logInWithPermission('EDIT_SITECONFIG');

        $fields = SiteConfig::create()->getCMSFields();

        $this->assertNull($fields->dataFieldByName('EduDexBearerToken'));
        $this->assertNull($fields->dataFieldByName('EduDexApiBaseUrl'));
    }

    public function testCmsFieldsForAdminWithoutTokenShowWarningAndMakeNoRequest(): void
    {
        $this->logInWithPermission('ADMIN');

        $fields = SiteConfig::create()->getCMSFields();

        $this->assertInstanceOf(TextField::class, $fields->dataFieldByName('EduDexApiBaseUrl'));
        $this->assertInstanceOf(TextField::class, $fields->dataFieldByName('EduDexBearerToken'));
        $this->assertNotNull($fields->findTab('Root.EduDex'));

        $status = $fields->fieldByName('Root.EduDex.EduDexStatus');
        $this->assertInstanceOf(LiteralField::class, $status);
        $this->assertStringContainsString('No bearer token configured', $status->getContent());
    }
}
