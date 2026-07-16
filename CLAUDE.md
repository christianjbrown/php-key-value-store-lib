# CLAUDE.md

Guidance for working in this repository. Match the existing conventions exactly — this codebase is
small, uniform, and highly opinionated, so new code should be indistinguishable from what's here.

## What this is

A thin, strongly-typed PHP 8.5+ library of interchangeable **key-value store** implementations. Every
store is a simple `get`/`set` of a single `?string` value (plus an optional `?int` TTL) behind one
tiny contract, `KeyValueStoreInterface`, so callers can swap the backing store without changing code.
It exists to hold small pieces of state — configuration flags, refresh tokens, cursors — and is
consumed by other libraries and a cloud function, so **the public API must not change** (class names,
the `ChristianBrown\KeyValueStore\` namespace, and every public method signature are frozen).

Three stores ship today:

- **`DatabaseKeyValueStore`** — persists to a MySQL table via Doctrine ORM, keyed by a string id.
- **`GoogleSecretKeyValueStore`** — reads/writes a Google Secret Manager secret (no TTL support).
- **`MemoryKeyValueStore`** — a per-process in-memory value (mostly for tests/defaults).

## Commands

Binaries install into `bin/` (Composer `bin-dir`), not `vendor/bin/`. Both `bin/` and `vendor/` are
gitignored and Composer-installed, so run `composer install` first. The style tooling comes from the
private `christianjbrown/php-code-quality-scripts` dev dependency (php-cs-fixer + PHP_CodeSniffer,
**Symfony2 coding standard**); installing it needs SSH/`COMPOSER_AUTH` access to the private repo.

| Task | Command |
| --- | --- |
| Run tests + coverage (opens HTML report) | `composer test` |
| Run tests, no coverage | `php -d memory_limit=-1 ./bin/phpunit --no-coverage` |
| Run one test | `php -d memory_limit=-1 ./bin/phpunit --filter DatabaseKeyValueStoreTest` |
| Static analysis (PHPStan level max) | `composer stan` |
| Check code style | `composer check-style` |
| Auto-fix code style | `composer fix-style` |
| Check / fix style on git diff only | `composer check-style-diff` / `composer fix-style-diff` |

Always run `composer fix-style` first (php-cs-fixer auto-fixes what it can), then `composer
check-style` to surface remaining violations that must be fixed by hand, then `composer stan`, then
`composer test` before finishing. If the `composer stan` wrapper runs out of memory, invoke PHPStan
directly: `./bin/phpstan analyse --no-progress --memory-limit=-1`. CI
(`.github/workflows/ci.yml`) runs the same three gates — style → PHPStan → PHPUnit-with-coverage — on
push/PR to `main`, supplying private-repo credentials via the `COMPOSER_AUTH` secret.

## Architecture

Everything lives flat under the `ChristianBrown\KeyValueStore\` namespace (`src/`), mirrored under
`ChristianBrown\KeyValueStore\Tests\` (`tests/`).

- **`KeyValueStoreInterface`** — the shared contract every store implements: `getTtl(): ?int`,
  `getValue(): ?string`, `setValue(?string $value, ?int $ttl = null): self`.
- **`DatabaseKeyValueStore` / `DatabaseKeyValueStoreInterface`** — constructed with an
  `EntityManagerInterface`, a `class-string` naming the Doctrine entity, and the row's string key. It
  resolves the entity's `EntityRepository` up front and does `findOneBy([FIELD_ID => $key])` on each
  read/write, inserting a fresh entity on first `setValue`. The entity class must implement
  `DatabaseKeyValueStoreEntityInterface`; the constructor guards this with `is_a(..., true)` and throws
  `InvalidArgumentException` otherwise.
- **`AbstractDatabaseKeyValueStoreEntity` / `DatabaseKeyValueStoreEntityInterface`** — the Doctrine
  `#[ORM\MappedSuperclass]` that stores extend to get the `id` / `ttl` / `value` columns and their
  accessors for free. See the deliberate deviation below.
- **`GoogleSecretKeyValueStore` / `GoogleSecretKeyValueStoreInterface`** — wraps Google's
  `SecretManagerServiceClient`. The client is **constructor-injected** so it is fully mockable; the
  static `create()` factory is the production convenience that builds a real client from
  `GOOGLE_APPLICATION_CREDENTIALS`. `getValue()` reads the `/versions/latest` version; `setValue()`
  adds a new secret version. TTL is unsupported, so `getTtl()` and a TTL-bearing `setValue()` throw.
  Secret-access failures are normalized into `GoogleSecretKeyValueStoreException`.
- **`GoogleSecretKeyValueStoreException` / `GoogleSecretKeyValueStoreExceptionInterface`** — the one
  library-specific exception (a `RuntimeException`), so callers can `catch` the interface.
- **`MemoryKeyValueStore` / `MemoryKeyValueStoreInterface`** — trivial in-process holder.

## Conventions (follow all of these)

- `declare(strict_types=1);` on every file, immediately after `<?php`.
- **Every concrete class is `final` and implements a matching `...Interface`** in the same namespace
  (`MemoryKeyValueStore`/`MemoryKeyValueStoreInterface`,
  `GoogleSecretKeyValueStore`/`GoogleSecretKeyValueStoreInterface`).
- **Constants live on the interface, not the class**: the Doctrine lookup field
  (`DatabaseKeyValueStoreInterface::FIELD_ID`), the secret version suffix
  (`GoogleSecretKeyValueStoreInterface::VERSION_LATEST`), and **every** exception message template
  (`*_SPRINTF`, `*_FAILED`, `TTL_NOT_SUPPORTED`, …). Message text never appears as a literal in a class
  body — reference the interface constant (via `self::` from the implementing class).
- **No constructor property promotion** — declare typed `private` properties and assign them in the
  constructor body. Class members (properties then methods) are ordered **alphabetically**.
- Import functions with `use function sprintf;` etc. (after class imports, blank line between) and call
  them unqualified. Note php-cs-fixer's `mb_str_functions` rule rewrites string calls to their `mb_*`
  equivalents (e.g. `trim` → `mb_trim`) — let it, don't fight it.
- Full type declarations on all params/returns; express generics/array shapes via `@param`/`@return`/
  `@var` docblocks (e.g. `DatabaseKeyValueStore::$repository` is
  `@var EntityRepository<DatabaseKeyValueStoreEntityInterface>`). Public methods that can throw carry
  `@throws` docblocks naming the concrete exception interface.
- **Do not add a lone constructor `@param` for one argument.** The Symfony/PEAR `FunctionComment` sniff
  maps a single `@param` to the *first* parameter positionally, so a `@param` for only the second
  argument fails style. Prefer expressing the constraint another way — `DatabaseKeyValueStore`'s
  `$entityClassName` is left as a plain `string` and narrowed to
  `class-string<DatabaseKeyValueStoreEntityInterface>` by the `is_a($x, ..., true)` guard, which
  PHPStan understands with no docblock.
- Dependencies are constructor-injected and typed against interfaces (`EntityManagerInterface`) or the
  concrete external SDK class (`SecretManagerServiceClient`) so everything is mockable.

### Deliberate deviation: the abstract mapped-superclass

`AbstractDatabaseKeyValueStoreEntity` is the one class that is **`abstract`, not `final`** — it is a
Doctrine `#[ORM\MappedSuperclass]`, whose entire purpose is to be extended by a consumer's concrete
`#[ORM\Entity]`. This is an intentional, isolated exception to the "every concrete class is final"
rule (the same kind of carve-out as `php-gcp-function-lib`'s `AbstractJsonResponse`). Keep it abstract.
Its public accessors are `final` (enforced by the `final_public_method_for_abstract_class` fixer rule),
so tests exercise it through a concrete fixture subclass (`Tests\TestDatabaseKeyValueStoreEntity`)
rather than a partial mock — you cannot mock a `final` method. Do not introduce any other abstract base
class.

## Testing

The `phpunit.xml` config is strict (`requireCoverageMetadata`, `beStrictAboutCoverageMetadata`,
`failOnRisky`, `failOnWarning`, `restrictNotices`/`restrictWarnings`, path coverage).

- **Keep line, branch, method, class, AND path coverage at 100%.** Every guard — each "entity
  exists / does not exist" branch, each `ApiException` → `GoogleSecretKeyValueStoreException`
  translation, the TTL-unsupported throws — must be exercised. Run `composer test` and check the report
  (text summary to stdout + HTML at `.phpunit.cache/code-coverage-html/index.html`) before finishing;
  the suite currently sits at 100% on all five metrics — keep it there. There are no loops in `src/`,
  so path coverage is currently just the branch combinations; if you add list processing, prefer array
  functions (`array_map`/`array_filter`) over `foreach`, which spawns unreachable back-edge paths.
- **Every test class needs a `#[CoversClass(...)]` attribute** (may list more than one) or the run
  fails. Use PHPUnit 12 **attributes, not annotations**: `#[CoversClass]`, `#[DataProvider]`,
  `#[TestWith]`.
- Tests mirror `src/` under `tests/`, one `final class XTest extends TestCase` per class, methods named
  `test<Method><Scenario>`. `tests/TestDatabaseKeyValueStoreEntity.php` is a shared **fixture**, not a
  test (no `Test.php` suffix, so PHPUnit does not collect it).
- **Double every collaborator, and pick the right kind of double** (PHPUnit 12 emits a notice for a
  `createMock()` that is never given an expectation, so don't reach for a mock by default):
  - **`self::createStub(SomeInterface::class)`** for a *pure return-value double* — one you only feed
    canned answers (`->method(...)->willReturn(...)`/`->willThrowException(...)`) or pass through
    unconfigured. Do **not** call `->with()` on a stub. The read paths stub `EntityManagerInterface`,
    `EntityRepository`, and the Google SDK classes this way.
  - **`self::createMock(SomeInterface::class)` with `->expects(self::once())`** for a *verified
    collaborator* — one whose call you assert on via `->with(...)`. `DatabaseKeyValueStoreTest` mocks
    the `EntityManager` to prove `persist()`/`flush()` are called; `GoogleSecretKeyValueStoreTest`
    mocks the client to prove `addSecretVersion()` gets the right path + payload.
  - Both factories are **static** — call them `self::createStub(...)`/`self::createMock(...)`.
- Assert statically (`self::assertSame`) and reference the **same interface constants** production code
  uses for expected exception messages (`sprintf(GoogleSecretKeyValueStoreInterface::…_SPRINTF, …)`),
  so no strings are hardcoded in tests.
- `GoogleSecretKeyValueStore::create()` is covered by real client construction against
  `tests/test-credentials.json` (success) and a missing file (failure); it is the one path that
  touches the real Google SDK, and it stays green because the SDK only validates credentials lazily.

## Adding a feature

1. Add the store/class + its matching `...Interface` in `src/`, with any constants (field names,
   message templates) on the interface. Concrete classes are `final`.
2. Constructor-inject every collaborator (typed against an interface, or the external SDK class) so it
   stays mockable — do not `new` a dependency inside a method except in a static `create()` factory.
3. Add a matching `#[CoversClass]` test under `tests/`, doubling all collaborators per the rules above.
4. Run `composer fix-style`, then `composer check-style`, then `composer stan`, then `composer test`
   and **confirm the coverage report is 100%** on classes, lines, paths, methods, and branches.
5. Never change an existing public method signature, class name, or namespace — external consumers
   depend on them.
