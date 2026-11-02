# Changelog

All notable changes to this package. Versions follow [semantic versioning](https://semver.org/).
Entries before 1.1.0 were not recorded here; see the
[GitHub releases and tags](https://github.com/restruct/edudex-api-client/tags).

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
  `cache_ttl` is unused ([#4](https://github.com/restruct/edudex-api-client/issues/4)).
