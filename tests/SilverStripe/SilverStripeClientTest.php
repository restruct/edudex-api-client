<?php

namespace Restruct\EduDex\Tests\SilverStripe;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Restruct\EduDex\Client;
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Integration\SilverStripe\SilverStripeClient;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

/**
 * SilverStripeClient: Config API settings, environment token, Injector mapping
 *
 * Needs a booted Silverstripe 5 app (CI runs it in a host project); excluded from
 * the standalone suite in phpunit.xml.dist. Written for PHPUnit 9 (SS5's runner).
 */
class SilverStripeClientTest extends SapphireTest
{
    protected $usesDatabase = false;

    /**
     * Environment variables before the test
     *
     * @var array
     */
    private array $envBackup = [];

    /**
     * Recorded Guzzle transactions
     *
     * @var array
     */
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->envBackup = Environment::getVariables();
        Environment::setEnv('EDUDEX_API_TOKEN', 'env-token');
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);
        parent::tearDown();
    }

    /**
     * Guzzle config that answers one list call and records it
     *
     * @return array
     */
    private function recordingConfig(): array
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"organizations":[]}')]));
        $stack->push(Middleware::history($this->history));

        return ['handler' => $stack];
    }

    public function testInjectorMapsBaseClientToSilverStripeClient(): void
    {
        # _config/edudex.yml maps Restruct\EduDex\Client onto SilverStripeClient
        $client = Injector::inst()->create(Client::class);

        $this->assertInstanceOf(SilverStripeClient::class, $client);
    }

    public function testDefaultsMatchTheSpecServer(): void
    {
        $client = new SilverStripeClient(null, null, $this->recordingConfig());
        $client->organizations()->list();

        $request = $this->history[0]['request'];
        $this->assertSame('https://api.edudex.nl/data/v1/organizations', (string) $request->getUri());
        $this->assertSame('Bearer env-token', $request->getHeaderLine('Authorization'));
        $this->assertSame(30, $this->history[0]['options']['timeout']);
    }

    public function testBaseUrlAndTimeoutComeFromConfig(): void
    {
        Config::modify()->set(SilverStripeClient::class, 'api_base_url', 'https://cfg.example.test/v2/');
        Config::modify()->set(SilverStripeClient::class, 'timeout', 7);

        $client = new SilverStripeClient(null, null, $this->recordingConfig());
        $client->organizations()->list();

        $this->assertSame('https://cfg.example.test/v2/organizations', (string) $this->history[0]['request']->getUri());
        $this->assertSame(7, $this->history[0]['options']['timeout']);
    }

    public function testConstructorArgumentsWinOverConfigAndEnvironment(): void
    {
        Config::modify()->set(SilverStripeClient::class, 'api_base_url', 'https://cfg.example.test/v2/');
        Config::modify()->set(SilverStripeClient::class, 'timeout', 7);

        $client = new SilverStripeClient('arg-token', 'https://arg.example.test/v3', $this->recordingConfig() + ['timeout' => 3]);
        $client->organizations()->list();

        $request = $this->history[0]['request'];
        $this->assertSame('https://arg.example.test/v3/organizations', (string) $request->getUri());
        $this->assertSame('Bearer arg-token', $request->getHeaderLine('Authorization'));
        $this->assertSame(3, $this->history[0]['options']['timeout']);
    }

    public function testMissingTokenThrows(): void
    {
        Environment::setEnv('EDUDEX_API_TOKEN', '');
        # The base Client also falls back to getenv(); make sure that is empty too
        $previous = getenv('EDUDEX_API_TOKEN');
        putenv('EDUDEX_API_TOKEN');

        try {
            $this->expectException(AuthenticationException::class);
            new SilverStripeClient();
        } finally {
            if ($previous !== false) {
                putenv('EDUDEX_API_TOKEN=' . $previous);
            }
        }
    }

    public function testDebugAndCacheTtlSettings(): void
    {
        $client = new SilverStripeClient();
        $this->assertFalse($client->isDebugEnabled());
        $this->assertSame(3600, $client->getCacheTTL());

        # No request is sent here: with debug on, Guzzle would print the transfer (issue #4)
        Config::modify()->set(SilverStripeClient::class, 'debug', true);
        Config::modify()->set(SilverStripeClient::class, 'cache_ttl', 60);
        $client = new SilverStripeClient();
        $this->assertTrue($client->isDebugEnabled());
        $this->assertSame(60, $client->getCacheTTL());
    }

    /**
     * Issue #4: the "debug" setting used to be handed to Guzzle as its own "debug"
     * option, which dumps the transfer (bearer token included) into the output
     */
    public function testDebugSettingNeverReachesGuzzle(): void
    {
        Config::modify()->set(SilverStripeClient::class, 'debug', true);

        $client = new SilverStripeClient(null, null, $this->recordingConfig());
        $client->organizations()->list();

        $this->assertTrue($client->isDebugEnabled());
        $this->assertArrayNotHasKey('debug', $this->history[0]['options']);
        $this->assertArrayNotHasKey('cache_ttl', $this->history[0]['options']);
    }
}
