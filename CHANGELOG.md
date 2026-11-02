# Changelog

All notable changes to this package. Versions follow [semantic versioning](https://semver.org/).
Entries before 1.1.0 were not recorded here; see the
[GitHub releases and tags](https://github.com/restruct/edudex-api-client/tags).

## 1.2.0 - 2026-11-02

Fixes [#4](https://github.com/restruct/edudex-api-client/issues/4). Public signatures are
unchanged; minor because behaviour changes and a setting is deprecated.

### Security

- The `debug` setting is no longer passed to Guzzle. Guzzle's own `debug` option prints the whole
  transfer, the `Authorization: Bearer` header included, to STDOUT or `php://output`, so with
  `debug: true` the token could end up in a CMS response. With `debug` on and no logger given,
  `SilverStripeClient` now logs requests and responses to the Silverstripe logger instead.

### Fixed

- A `headers` key in the client config adds to the default headers instead of replacing them, and
  can no longer drop or replace the bearer token.
- The package's own config keys (`bearer_token`, `api_base_url`, `debug`, `cache_ttl`) no longer
  reach Guzzle as options. Other keys still pass through.
- The CMS settings tab no longer calls the API every time Settings is opened. A "Test connection"
  link runs the check on demand (`?edudexcheck=1`).
- `SiteConfig::getEduDexClient()` builds the client through the Injector, so it returns a
  `SilverStripeClient` and that class's `timeout` and `debug` config apply.

### Deprecated

- `SilverStripeClient.cache_ttl` and `getCacheTTL()`: the client has never cached anything. They
  will be removed in 2.0.

## 1.1.0 - 2026-11-02

### Fixed

- `Suppliers::upsertProgram()` and `deleteProgram()` now send their request to
  `suppliers/{orgUnitId}/programs/{programId}/{clientId}`, the only path the EDU-DEX spec defines
  PUT and DELETE on. They used to drop the client id and call `.../programs/{programId}`, which only
  has GET ([#1](https://github.com/restruct/edudex-api-client/issues/1)).
- `Programs::bulk()` now sends `$viewerOrgUnitId` and `$viewDiscountsForOrgUnitId` as the body
  fields `viewer` and `viewDiscountsFor` defined by the spec. They used to be dropped
  ([#1](https://github.com/restruct/edudex-api-client/issues/1)).

### Removed

- The protected `Suppliers::put()`, `Suppliers::delete()` and `Programs::post()` helpers. Nothing
  called them, and calling them threw an `Error` (they called parent methods that do not exist).

### Added

- A PHPUnit suite that checks every request against the bundled OpenAPI spec, with no network
  calls, and CI on PHP 8.1 to 8.5 plus a Silverstripe 5 host project.

### Known issues

- Several response shapes and request bodies disagree with the bundled spec; most visibly,
  `ValidationResult::isValid()` is always `true` for a spec-shaped validation response
  ([#3](https://github.com/restruct/edudex-api-client/issues/3)).
- Config handling: `debug` is passed to Guzzle as is, the settings tab calls the API on every load,
  `cache_ttl` is unused ([#4](https://github.com/restruct/edudex-api-client/issues/4)). Fixed in 1.2.0.
