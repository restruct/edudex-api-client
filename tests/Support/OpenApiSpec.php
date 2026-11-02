<?php

namespace Restruct\EduDex\Tests\Support;

use RuntimeException;

/**
 * Minimal reader for the bundled openapi-edudex-spec.json
 *
 * Only what the tests need to check a recorded request against the spec:
 * find the operation for a method + path, list its query parameters and
 * resolve its JSON request body schema. Not a general OpenAPI validator.
 */
final class OpenApiSpec
{
    /**
     * Decoded spec, loaded once per process
     *
     * @var array|null
     */
    private static ?array $spec = null;

    /**
     * Decoded spec document
     *
     * @return array
     */
    public static function document(): array
    {
        if (self::$spec === null) {
            $file = dirname(__DIR__, 2) . '/openapi-edudex-spec.json';
            $decoded = json_decode((string) file_get_contents($file), true);
            if (!is_array($decoded)) {
                throw new RuntimeException("Cannot read OpenAPI spec at {$file}");
            }
            self::$spec = $decoded;
        }

        return self::$spec;
    }

    /**
     * Base path of the API server, e.g. "/data/v1"
     *
     * @return string
     */
    public static function basePath(): string
    {
        $url = self::document()['servers'][0]['url'];
        return rtrim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    /**
     * Find the spec path template that defines $method for a concrete path
     *
     * @param string $method HTTP method, any case
     * @param string $path Concrete path relative to the server base, with leading slash
     * @return string|null The template (e.g. "/suppliers/{orgUnitId}") or null if none matches
     */
    public static function findPathTemplate(string $method, string $path): ?string
    {
        $method = strtolower($method);

        foreach (self::document()['paths'] as $template => $item) {
            if (!isset($item[$method])) {
                continue;
            }
            # Each {param} matches exactly one non-empty path segment
            $regex = '#^' . preg_replace('#\\\\\{[^/]+?\\\\\}#', '[^/]+', preg_quote($template, '#')) . '$#';
            if (preg_match($regex, $path)) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Query parameters the spec declares for an operation (path-level and operation-level)
     *
     * @param string $method
     * @param string $template
     * @return array<string, bool> name => required
     */
    public static function queryParameters(string $method, string $template): array
    {
        $item = self::document()['paths'][$template];
        $params = array_merge($item['parameters'] ?? [], $item[strtolower($method)]['parameters'] ?? []);

        $query = [];
        foreach ($params as $param) {
            $param = self::resolve($param);
            if (($param['in'] ?? null) === 'query') {
                $query[$param['name']] = (bool) ($param['required'] ?? false);
            }
        }

        return $query;
    }

    /**
     * Resolved JSON request body schema for an operation, or null if it has none
     *
     * @param string $method
     * @param string $template
     * @return array|null
     */
    public static function requestBodySchema(string $method, string $template): ?array
    {
        $operation = self::document()['paths'][$template][strtolower($method)];
        $schema = $operation['requestBody']['content']['application/json']['schema'] ?? null;

        return $schema === null ? null : self::resolve($schema);
    }

    /**
     * Follow a local "$ref" (JSON pointer into this document) until a non-ref node is reached
     *
     * @param array $node
     * @return array
     */
    public static function resolve(array $node): array
    {
        while (isset($node['$ref'])) {
            $ref = $node['$ref'];
            if (!str_starts_with($ref, '#/')) {
                throw new RuntimeException("Only local refs are supported, got {$ref}");
            }
            $target = self::document();
            foreach (explode('/', substr($ref, 2)) as $segment) {
                $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
                if (!is_array($target) || !array_key_exists($segment, $target)) {
                    throw new RuntimeException("Unresolvable ref {$ref}");
                }
                $target = $target[$segment];
            }
            $node = $target;
        }

        return $node;
    }
}
