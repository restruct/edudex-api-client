<?php

namespace Restruct\EduDex\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Restruct\EduDex\Client;
use Restruct\EduDex\Tests\Support\OpenApiSpec;

/**
 * Base class for tests that drive the real Client + GuzzleClient stack
 *
 * Requests never leave the process: a Guzzle MockHandler answers them and the
 * history middleware records what was sent, so each test can assert method,
 * path, query, headers and body, and check them against the bundled spec.
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * Token used for every client built by these tests
     */
    protected const TOKEN = 'unit-test-token';

    /**
     * Recorded transactions (request, response, error, options)
     *
     * @var array<int, array{request: RequestInterface}>
     */
    protected array $history = [];

    /**
     * Mock handler queue for the current client
     *
     * @var MockHandler
     */
    protected MockHandler $mock;

    /**
     * Build a Client whose Guzzle stack answers from $responses and records requests
     *
     * @param array $responses Responses or exceptions, in order
     * @param array $config Extra client config
     * @param string|null $baseUrl
     * @return Client
     */
    protected function makeClient(array $responses = [], array $config = [], ?string $baseUrl = null): Client
    {
        $this->history = [];
        $this->mock = new MockHandler($responses);
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));

        # The client passes $config through to the Guzzle constructor, so a custom
        # handler is the supported way to swap the transport without touching src/
        return new Client(self::TOKEN, $baseUrl, ['handler' => $stack] + $config);
    }

    /**
     * JSON response helper
     *
     * @param mixed $data
     * @param int $status
     * @return Response
     */
    protected static function json(mixed $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    /**
     * Empty 204 response, as the spec defines for every DELETE
     *
     * @return Response
     */
    protected static function noContent(): Response
    {
        return new Response(204);
    }

    /**
     * The only request sent so far (fails if there were none or several)
     *
     * @return RequestInterface
     */
    protected function onlyRequest(): RequestInterface
    {
        $this->assertCount(1, $this->history, 'Expected exactly one request');
        return $this->history[0]['request'];
    }

    /**
     * Decoded JSON body of a request (null when the body is empty)
     *
     * @param RequestInterface $request
     * @return mixed
     */
    protected static function body(RequestInterface $request): mixed
    {
        $raw = (string) $request->getBody();
        return $raw === '' ? null : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Query string of a request as an array
     *
     * @param RequestInterface $request
     * @return array
     */
    protected static function query(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);
        return $query;
    }

    /**
     * Assert the single request sent matches method, path, query and body,
     * and that the spec defines that operation with that query and body shape
     *
     * @param string $method
     * @param string $path Path relative to the API base, without leading slash
     * @param array $query Expected query parameters (exact)
     * @param mixed $body Expected decoded JSON body (exact); null means no body expected
     * @return RequestInterface
     */
    protected function assertSentRequest(string $method, string $path, array $query = [], mixed $body = null): RequestInterface
    {
        $request = $this->onlyRequest();

        $this->assertSame($method, $request->getMethod(), 'HTTP method');
        $this->assertSame(OpenApiSpec::basePath() . '/' . $path, $request->getUri()->getPath(), 'Request path');
        $this->assertSame($query, self::query($request), 'Query parameters');
        $this->assertSame($body, self::body($request), 'JSON body');

        $this->assertConformsToSpec($request);

        return $request;
    }

    /**
     * Assert a request is one the bundled spec defines: method + path, query
     * parameter names, and the top-level shape of the JSON body
     *
     * @param RequestInterface $request
     * @return void
     */
    protected function assertConformsToSpec(RequestInterface $request): void
    {
        $method = $request->getMethod();
        $fullPath = $request->getUri()->getPath();
        $base = OpenApiSpec::basePath();

        $this->assertStringStartsWith($base . '/', $fullPath, 'Request is not under the spec server base path');
        $path = substr($fullPath, strlen($base));

        $template = OpenApiSpec::findPathTemplate($method, $path);
        $this->assertNotNull($template, "The spec defines no {$method} operation matching {$path}");

        # Query: every sent parameter is declared, every required one is sent
        $declared = OpenApiSpec::queryParameters($method, $template);
        $sent = self::query($request);
        foreach (array_keys($sent) as $name) {
            $this->assertArrayHasKey($name, $declared, "Query parameter '{$name}' is not in the spec for {$method} {$template}");
        }
        foreach ($declared as $name => $required) {
            if ($required) {
                $this->assertArrayHasKey($name, $sent, "Required query parameter '{$name}' missing for {$method} {$template}");
            }
        }

        # Body: top-level shape only (required keys, no undeclared keys when the schema is closed)
        $schema = OpenApiSpec::requestBodySchema($method, $template);
        $body = self::body($request);
        if ($schema === null) {
            return;
        }
        $this->assertNotNull($body, "The spec requires a JSON body for {$method} {$template}");

        if (($schema['type'] ?? null) === 'array') {
            $this->assertTrue(is_array($body) && array_is_list($body), "Body for {$method} {$template} must be a JSON array");
            return;
        }

        if (($schema['type'] ?? null) === 'object') {
            $this->assertIsArray($body);
            foreach ($schema['required'] ?? [] as $key) {
                $this->assertArrayHasKey($key, $body, "Required body field '{$key}' missing for {$method} {$template}");
            }
            if (($schema['additionalProperties'] ?? true) === false) {
                $allowed = array_keys($schema['properties'] ?? []);
                foreach (array_keys($body) as $key) {
                    $this->assertContains($key, $allowed, "Body field '{$key}' is not in the spec for {$method} {$template}");
                }
            }
        }
    }
}
