# Changelog

All notable changes to this package. Versions follow [semantic versioning](https://semver.org/).
Entries before 1.1.0 were not recorded here; see the
[GitHub releases and tags](https://github.com/restruct/edudex-api-client/tags).

## 1.3.0 - 2026-11-02

Fixes [#3](https://github.com/restruct/edudex-api-client/issues/3): requests and responses that
disagreed with the bundled OpenAPI spec. Every change below is confirmed against that spec and its
tests only, **not against the live API**. Existing signatures keep working (two methods gain an
optional argument); minor because of the new methods and the changed behaviour.

### Fixed

- `ValidationResult` reads the spec's `validates` and `validationMessages` (`level`, `ruleName`,
  `forClient`). Before, a spec-shaped response hydrated with no messages, so `isValid()` was always
  `true` and invalid data passed the "validate, then upsert" flow. `isValid()` now also honours
  `validates: false` without messages. `getErrors()`, `getWarnings()` and `getInfo()` return lists
  (no gaps in the keys).
- A 400 whose body carries `validationMessages` (the spec's `put_badrequest`) throws a
  `ValidationException`; `ValidationException` reads `level` as well as `severity`.
- `Programs::getSuccessful()` / `getFailed()` read the spec's `programs` list (each item with `data`
  or `error`) and build each `Program` from the ids and `data`. The old keys are still read as a
  fallback.
- `Suppliers::listPrograms()` and `listDiscounts()` read the spec's `items` key (they always
  returned empty arrays). `listPrograms()` applies its client id filter itself instead of sending a
  query parameter the spec does not define.
- `Suppliers::getProgram()` fetches through `POST /programs/bulk` and returns that program; it
  throws an `ApiException` with the API's error when the program cannot be fetched. It used to GET
  the client list of the program and hydrate it into an empty `Program`.
- `Suppliers::upsertProgram()` returns the program as submitted, with its ids. It used to hydrate
  the API's status response into a `Program` whose typed properties were unset, so reading them
  threw an `Error`.
- `Organizations::bulkAddPrograms()` / `bulkRemovePrograms()` send a bare array of
  `{supplierId, programId}`, as the spec defines, instead of `{"programs": [...]}`; other keys,
  such as the `clientId` the old docblock asked for, are dropped.
- `Organizations::addSupplierToDynamicCatalog()` sends the required `selection` (new optional
  argument, default `all`); `testWebhook()` sends the required `event` (new optional argument,
  default `catalog`).

### Added

- `Suppliers::upsertProgramWithStatus()`: the API's status for a program PUT (`status`,
  `validationMessages`, `lastChange`).
- `Suppliers::listProgramClients()`: the clients a program is published for.
- `Organizations::listStaticCatalogPrograms()`, `getStaticCatalogChanges()`,
  `listDynamicCatalogPrograms()`, `getDynamicCatalogChanges()`, `listDynamicCatalogSuppliers()`,
  `getDynamicCatalogSupplier()`.
- `Programs::hydrateBulkItem()`.

### Deprecated

- `Organizations::listCatalogs()`: the spec has no `/organizations/{orgUnitId}/catalogs`. It now
  combines `listStaticCatalogs` and `listDynamicCatalogs` raw results in two requests.

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
  ([#3](https://github.com/restruct/edudex-api-client/issues/3)). Fixed in 1.3.0.
- Config handling: `debug` is passed to Guzzle as is, the settings tab calls the API on every load,
  `cache_ttl` is unused ([#4](https://github.com/restruct/edudex-api-client/issues/4)). Fixed in 1.2.0.
