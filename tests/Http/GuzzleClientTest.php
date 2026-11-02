<?php

namespace Restruct\EduDex\Tests\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Restruct\EduDex\Exceptions\ApiException;
use Restruct\EduDex\Exceptions\AuthenticationException;
use Restruct\EduDex\Exceptions\EduDexException;
use Restruct\EduDex\Exceptions\ValidationException;
use Restruct\EduDex\Http\GuzzleClient;

/**
 * GuzzleClient: how each verb builds its request, how responses are parsed and
 * how HTTP errors map onto the package's exceptions
 */
class GuzzleClientTest extends TestCase
{
    /**
     * @var array<int, array>
     */
    private array $history = [];

    /**
     * Log records written by the client: [level, message, context]
     *
     * @var array<int, array{string, string, array}>
     */
    private array $logs = [];

    private function http(array $responses): GuzzleClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $logs = &$this->logs;
        $logger = new class ($logs) extends AbstractLogger {
            private array $records;

            public function __construct(array &$records)
            {
                $this->records = &$records;
            }

            # Untyped $message: compatible with psr/log 1, 2 and 3 alike
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message, $context];
            }
        };

        return new GuzzleClient('https://api.example.test/data/v1/', 'tok', ['handler' => $stack], $logger);
    }

    private function request(): \Psr\Http\Message\RequestInterface
    {
        $this->assertCount(1, $this->history);
        return $this->history[0]['request'];
    }

    public function testGetSendsQueryAndExtraHeaders(): void
    {
        $http = $this->http([new Response(200, [], '{"a":1}')]);

        $this->assertSame(['a' => 1], $http->get('things', ['x' => 'y', 'n' => 2], ['X-Extra' => 'e']));

        $request = $this->request();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/data/v1/things', $request->getUri()->getPath());
        $this->assertSame('x=y&n=2', $request->getUri()->getQuery());
        $this->assertSame('e', $request->getHeaderLine('X-Extra'));
        # Per-request headers add to the defaults, they do not replace them
        $this->assertSame('Bearer tok', $request->getHeaderLine('Authorization'));
        $this->assertSame('', (string) $request->getBody());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function bodyVerbs(): array
    {
        return ['post' => ['post'], 'put' => ['put'], 'patch' => ['patch']];
    }

    #[DataProvider('bodyVerbs')]
    public function testBodyVerbsSendJson(string $verb): void
    {
        $http = $this->http([new Response(200, [], '{"ok":true}')]);

        $this->assertSame(['ok' => true], $http->{$verb}('things/1', ['name' => 'n', 'list' => [1, 2]]));

        $request = $this->request();
        $this->assertSame(strtoupper($verb), $request->getMethod());
        $this->assertSame('/data/v1/things/1', $request->getUri()->getPath());
        $this->assertSame('', $request->getUri()->getQuery());
        $this->assertSame(['name' => 'n', 'list' => [1, 2]], json_decode((string) $request->getBody(), true));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testDeleteSendsNoBodyAndReturnsEmptyArrayOn204(): void
    {
        $http = $this->http([new Response(204)]);

        $this->assertSame([], $http->delete('things/1'));

        $request = $this->request();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('', (string) $request->getBody());
    }

    public function testLeadingSlashDoesNotEscapeTheBasePath(): void
    {
        $http = $this->http([new Response(200, [], '{}')]);

        $http->get('/things');

        $this->assertSame('/data/v1/things', $this->request()->getUri()->getPath());
    }

    public function testRawRequestPassesOptions(): void
    {
        $http = $this->http([new Response(200, [], '[1,2]')]);

        $this->assertSame([1, 2], $http->request('POST', 'raw', ['query' => ['q' => '1'], 'json' => ['b' => 2]]));

        $request = $this->request();
        $this->assertSame('q=1', $request->getUri()->getQuery());
        $this->assertSame('{"b":2}', (string) $request->getBody());
    }

    public function testJsonNullBodyBecomesEmptyArray(): void
    {
        $http = $this->http([new Response(200, [], 'null')]);

        $this->assertSame([], $http->get('things'));
    }

    public function testInvalidJsonThrowsWithBodyInContext(): void
    {
        $http = $this->http([new Response(200, [], '<html>oops</html>')]);

        try {
            $http->get('things');
            $this->fail('Expected an EduDexException');
        } catch (EduDexException $e) {
            $this->assertStringStartsWith('Invalid JSON response', $e->getMessage());
            $this->assertSame(200, $e->getCode());
            $this->assertSame('<html>oops</html>', $e->getContextValue('body'));
        }
    }

    public function testRequestAndResponseAreLoggedAtDebug(): void
    {
        $http = $this->http([new Response(200, [], '{"a":1}')]);

        $http->get('things');

        $this->assertSame('debug', $this->logs[0][0]);
        $this->assertSame('EduDex API Request: GET things', $this->logs[0][1]);
        $this->assertSame('EduDex API Response: GET things', $this->logs[1][1]);
        $this->assertSame(200, $this->logs[1][2]['status']);
    }

    public function testUnauthorizedMapsToAuthenticationException(): void
    {
        $http = $this->http([new Response(401, [], '{"status":401,"error":"Token expired"}')]);

        try {
            $http->get('things');
            $this->fail('Expected an AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame('Token expired', $e->getMessage());
            $this->assertSame(401, $e->getCode());
        }
        $this->assertSame('error', end($this->logs)[0]);
    }

    public function testForbiddenMapsToAuthenticationException(): void
    {
        $http = $this->http([new Response(403, [], '{"status":403,"error":"No access"}')]);

        try {
            $http->get('things');
            $this->fail('Expected an AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame('No access', $e->getMessage());
            $this->assertSame(403, $e->getCode());
        }
    }

    public function testBadRequestWithMessagesMapsToValidationException(): void
    {
        $body = json_encode([
            'message' => 'Invalid',
            'messages' => [
                ['severity' => 'error', 'message' => 'Title missing'],
                ['severity' => 'warning', 'message' => 'Short description'],
            ],
        ]);
        $http = $this->http([new Response(400, [], $body)]);

        try {
            $http->put('things/1', []);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('Invalid', $e->getMessage());
            $this->assertSame(['Title missing'], $e->getErrorMessages());
            $this->assertSame(['Short description'], $e->getWarningMessages());
        }
    }

    public function testBadRequestWithoutMessagesKeepsBodyInContext(): void
    {
        $http = $this->http([new Response(400, [], '{"status":400,"error":"Bad input","code":"E1"}')]);

        try {
            $http->post('things', []);
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertNotInstanceOf(ValidationException::class, $e);
            $this->assertSame('Bad input', $e->getMessage());
            $this->assertSame(400, $e->getCode());
            $this->assertSame('E1', $e->getContextValue('code'));
            $this->assertTrue($e->isClientError());
        }
    }

    public function testNotFoundMapsToApiException(): void
    {
        $http = $this->http([new Response(404, [], '{"status":404,"error":"Program"}')]);

        try {
            $http->get('things/1');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(404, $e->getCode());
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    public function testRequestTimeoutStatusMapsToTimeout(): void
    {
        $http = $this->http([new Response(408)]);

        try {
            $http->get('things');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(408, $e->getCode());
            $this->assertSame('Request timeout', $e->getMessage());
        }
    }

    /**
     * @return array<string, array{int}>
     */
    public static function serverErrors(): array
    {
        return ['500' => [500], '502' => [502], '503' => [503]];
    }

    #[DataProvider('serverErrors')]
    public function testServerErrorsMapToServerError(int $status): void
    {
        $http = $this->http([new Response($status, [], '{"error":"Down"}')]);

        try {
            $http->get('things');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertTrue($e->isServerError());
            $this->assertSame('Down', $e->getMessage());
        }
    }

    public function testOtherClientErrorMapsToApiException(): void
    {
        $http = $this->http([new Response(422, [], '{"message":"Unprocessable"}')]);

        try {
            $http->post('things', []);
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            # "message" is used when there is no "error" key
            $this->assertSame('Unprocessable', $e->getMessage());
            $this->assertTrue($e->isClientError());
        }
    }

    public function testNonJsonErrorBodyFallsBackToGuzzleMessage(): void
    {
        $http = $this->http([new Response(500, [], 'Gateway exploded')]);

        try {
            $http->get('things');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertTrue($e->isServerError());
            $this->assertStringContainsString('500', $e->getMessage());
        }
    }

    public function testNetworkErrorMapsToApiExceptionWithCodeZero(): void
    {
        $http = $this->http([new ConnectException('Could not resolve host', new Request('GET', 'things'))]);

        try {
            $http->get('things');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertSame(0, $e->getCode());
            $this->assertSame('Could not resolve host', $e->getMessage());
        }
    }

    public function testConstructorExposesBaseUrlAndGuzzleClient(): void
    {
        $http = new GuzzleClient('https://api.example.test/data/v1///', 'tok');

        $this->assertSame('https://api.example.test/data/v1', $http->getBaseUrl());
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $http->getGuzzleClient());
    }
}
