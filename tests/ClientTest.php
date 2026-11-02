<?php

namespace Restruct\EduDex\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Restruct\EduDex\Client;
use Restruct\EduDex\Endpoints\Accreditors;
use Restruct\EduDex\Endpoints\Organizations;
use Restruct\EduDex\Endpoints\Programs;
use Restruct\EduDex\Endpoints\Suppliers;
use Restruct\EduDex\Endpoints\Validations;
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Http\ClientInterface;
use Restruct\EduDex\Http\GuzzleClient;

/**
 * Client construction: token, base URL and option resolution, headers, endpoint accessors
 */
class ClientTest extends ApiTestCase
{
    /**
     * EDUDEX_API_TOKEN as it was before the test (phpunit.xml.dist sets a placeholder)
     *
     * @var string|false
     */
    private string|false $envToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envToken = getenv('EDUDEX_API_TOKEN');
    }

    protected function tearDown(): void
    {
        # Restore the environment exactly, including "not set"
        putenv($this->envToken === false ? 'EDUDEX_API_TOKEN' : 'EDUDEX_API_TOKEN=' . $this->envToken);
        parent::tearDown();
    }

    /**
     * Client built with only a config array and recording handler (no explicit token/base URL)
     *
     * @param array $config
     * @param string|null $token
     * @return Client
     */
    private function clientWith(array $config, ?string $token = null): Client
    {
        $this->history = [];
        $this->mock = new MockHandler([self::json(['organizations' => []])]);
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));

        return new Client($token, null, ['handler' => $stack] + $config);
    }

    public function testSendsBearerTokenAndJsonHeaders(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])]);

        $client->organizations()->list();

        $request = $this->onlyRequest();
        $this->assertSame('Bearer ' . self::TOKEN, $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testDefaultBaseUrlIsTheSpecServer(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])]);

        $client->organizations()->list();

        $this->assertSame('https://api.edudex.nl/data/v1/organizations', (string) $this->onlyRequest()->getUri());
        $this->assertSame('https://api.edudex.nl/data/v1/', Client::DEFAULT_BASE_URL);
    }

    public function testCustomBaseUrlWithoutTrailingSlashKeepsItsPath(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], [], 'https://staging.example.test/data/v9');

        $client->organizations()->list();

        $this->assertSame('https://staging.example.test/data/v9/organizations', (string) $this->onlyRequest()->getUri());
    }

    public function testBaseUrlFromConfig(): void
    {
        $client = $this->clientWith(['bearer_token' => 'cfg-token', 'api_base_url' => 'https://cfg.example.test/v1/']);

        $client->organizations()->list();

        $this->assertSame('https://cfg.example.test/v1/organizations', (string) $this->onlyRequest()->getUri());
    }

    public function testTokenArgumentWinsOverConfigAndEnvironment(): void
    {
        putenv('EDUDEX_API_TOKEN=env-token');
        $client = $this->clientWith(['bearer_token' => 'cfg-token'], 'arg-token');

        $client->organizations()->list();

        $this->assertSame('Bearer arg-token', $this->onlyRequest()->getHeaderLine('Authorization'));
    }

    public function testTokenFromConfigWinsOverEnvironment(): void
    {
        putenv('EDUDEX_API_TOKEN=env-token');
        $client = $this->clientWith(['bearer_token' => 'cfg-token']);

        $client->organizations()->list();

        $this->assertSame('Bearer cfg-token', $this->onlyRequest()->getHeaderLine('Authorization'));
    }

    public function testTokenFromEnvironment(): void
    {
        putenv('EDUDEX_API_TOKEN=env-token');
        $client = $this->clientWith([]);

        $client->organizations()->list();

        $this->assertSame('Bearer env-token', $this->onlyRequest()->getHeaderLine('Authorization'));
    }

    public function testMissingTokenThrows(): void
    {
        putenv('EDUDEX_API_TOKEN');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);
        new Client();
    }

    public function testEmptyTokenThrows(): void
    {
        putenv('EDUDEX_API_TOKEN');

        $this->expectException(AuthenticationException::class);
        new Client('');
    }

    public function testDefaultTimeoutIsApplied(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])]);

        $client->organizations()->list();

        $this->assertSame(Client::DEFAULT_TIMEOUT, $this->history[0]['options']['timeout']);
    }

    public function testTimeoutFromConfigIsApplied(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], ['timeout' => 5]);

        $client->organizations()->list();

        $this->assertSame(5, $this->history[0]['options']['timeout']);
    }

    public function testFromConfig(): void
    {
        putenv('EDUDEX_API_TOKEN');
        $stack = HandlerStack::create(new MockHandler([self::json(['organizations' => []])]));
        $history = [];
        $stack->push(Middleware::history($history));

        $client = Client::fromConfig([
            'bearer_token' => 'cfg-token',
            'api_base_url' => 'https://cfg.example.test/v1',
            'handler' => $stack,
            'custom' => 'value',
        ]);
        $client->organizations()->list();

        $this->assertSame('Bearer cfg-token', $history[0]['request']->getHeaderLine('Authorization'));
        $this->assertSame('https://cfg.example.test/v1/organizations', (string) $history[0]['request']->getUri());
        $this->assertSame('value', $client->getConfig('custom'));
        $this->assertSame('fallback', $client->getConfig('missing', 'fallback'));
    }

    public function testDefaultHttpClientIsGuzzleWithResolvedBaseUrl(): void
    {
        $client = new Client('t', 'https://example.test/v1/');

        $http = $client->getHttpClient();
        $this->assertInstanceOf(GuzzleClient::class, $http);
        $this->assertSame('https://example.test/v1', $http->getBaseUrl());
    }

    public function testEndpointAccessorsReturnTheSameInstance(): void
    {
        $client = new Client('t');

        $this->assertInstanceOf(Organizations::class, $client->organizations());
        $this->assertInstanceOf(Suppliers::class, $client->suppliers());
        $this->assertInstanceOf(Accreditors::class, $client->accreditors());
        $this->assertInstanceOf(Programs::class, $client->programs());
        $this->assertInstanceOf(Validations::class, $client->validations());

        $this->assertSame($client->organizations(), $client->organizations());
        $this->assertSame($client->suppliers(), $client->suppliers());
        $this->assertSame($client->accreditors(), $client->accreditors());
        $this->assertSame($client->programs(), $client->programs());
        $this->assertSame($client->validations(), $client->validations());
    }

    public function testSetHttpClientRoutesEndpointsThroughIt(): void
    {
        $fake = $this->createMock(ClientInterface::class);
        $fake->expects($this->once())
            ->method('get')
            ->with('organizations', [], [])
            ->willReturn(['organizations' => []]);

        $client = (new Client('t'))->setHttpClient($fake);

        $this->assertSame($fake, $client->getHttpClient());
        $this->assertSame([], $client->organizations()->list());
    }
}
