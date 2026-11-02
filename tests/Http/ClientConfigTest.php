<?php

namespace Restruct\EduDex\Tests\Http;

use Restruct\EduDex\Tests\ApiTestCase;

/**
 * Which config keys reach Guzzle, and how a "headers" key combines with the defaults
 *
 * Regressions for https://github.com/restruct/edudex-api-client/issues/4
 */
class ClientConfigTest extends ApiTestCase
{
    /**
     * Guzzle request options of the single request sent
     *
     * @return array
     */
    private function sentOptions(): array
    {
        $this->onlyRequest();
        return $this->history[0]['options'];
    }

    /**
     * Issue #4: Guzzle's own "debug" option dumps the whole transfer, bearer token
     * included, to STDOUT/php://output. The client's "debug" setting must never reach it.
     */
    public function testDebugSettingNeverReachesGuzzle(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], ['debug' => true]);

        $client->organizations()->list();

        $this->assertArrayNotHasKey('debug', $this->sentOptions());
        $this->assertTrue($client->getConfig('debug'), 'The setting itself stays readable on the client');
    }

    /**
     * Issue #4: keys that only mean something to this package are not Guzzle options
     */
    public function testPackageOnlyKeysNeverReachGuzzle(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], [
            'cache_ttl' => 60,
            'bearer_token' => 'cfg-token',
            'api_base_url' => 'https://ignored.example.test/',
        ]);

        $client->organizations()->list();

        $options = $this->sentOptions();
        $this->assertArrayNotHasKey('cache_ttl', $options);
        $this->assertArrayNotHasKey('bearer_token', $options);
        $this->assertArrayNotHasKey('api_base_url', $options);
    }

    public function testOtherGuzzleOptionsStillPassThrough(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], [
            'verify' => false,
            'connect_timeout' => 2,
        ]);

        $client->organizations()->list();

        $options = $this->sentOptions();
        $this->assertFalse($options['verify']);
        $this->assertSame(2, $options['connect_timeout']);
    }

    /**
     * Issue #4: a "headers" key used to replace the default headers wholesale,
     * so the request went out without Authorization, Accept or Content-Type
     */
    public function testHeadersConfigAddsToTheDefaultsAndKeepsAuthorization(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], ['headers' => ['X-Foo' => 'bar']]);

        $client->organizations()->list();

        $request = $this->onlyRequest();
        $this->assertSame('bar', $request->getHeaderLine('X-Foo'));
        $this->assertSame('Bearer ' . self::TOKEN, $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    /**
     * Issue #4: the bearer token always wins over an Authorization header in config
     */
    public function testHeadersConfigCannotReplaceTheBearerToken(): void
    {
        $client = $this->makeClient([self::json(['organizations' => []])], ['headers' => ['Authorization' => 'Basic eA==']]);

        $client->organizations()->list();

        $this->assertSame('Bearer ' . self::TOKEN, $this->onlyRequest()->getHeaderLine('Authorization'));
    }
}
