# 0017. PHPUnit feature tests from the Breeze baseline

- **Status:** Accepted
- **Date:** 2026-09-20
- **Area:** Testing
- **Affects:** `tests/`, `phpunit.xml`, `composer.json`

## Context

`laravel/pao` (dev dependency) is a coverage-report generator, and `phpunit/phpunit
^12.5` is installed with coverage sourced from `app/`. So the intent to measure coverage
is present in the toolchain.

But the test suite as it stands contains **only** what `laravel/breeze` generated. There
are no tests for a single line of Bloom's own code: no model, no controller, no Form
Request, no API Resource, no middleware, no seeder.

The distinction that matters: Breeze's 23 tests are real tests, and they do pass. They
cover login, registration, logout, password reset, email verification, password
confirmation, password update, and profile editing — a genuine slice of the *framework's*
auth behaviour. What they do not cover is the part of the application someone wrote.

## Decision

We kept the **Breeze-generated PHPUnit feature tests as the baseline** and have not yet
added application tests. The configuration is `phpunit.xml`'s standard Laravel 13
scaffold:

- Two suites: `Unit` → `tests/Unit`, `Feature` → `tests/Feature`
- `<source><include><directory>app</directory></include></source>` for coverage
- Test-time environment overrides, all pointing at throwaway infrastructure:
  `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `CACHE_STORE=array`,
  `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`,
  `BCRYPT_ROUNDS=4` (so hashing is fast)
- `tests/TestCase.php` is a bare `abstract class TestCase extends BaseTestCase` — no
  `CreatesApplication` trait and **no shared helper methods** (no `actingAsApiUser()`,
  no record factory shortcuts)

The suite, in full:

| File | Tests | Covers |
| --- | --- | --- |
| `Feature/Auth/AuthenticationTest.php` | 4 | login screen, login, wrong password, logout |
| `Feature/Auth/RegistrationTest.php` | 2 | register screen, registration |
| `Feature/Auth/EmailVerificationTest.php` | 3 | verify screen, signed URL, bad hash |
| `Feature/Auth/PasswordResetTest.php` | 4 | forgot screen, link sent, reset screen, reset |
| `Feature/Auth/PasswordUpdateTest.php` | 2 | password changed, wrong `current_password` |
| `Feature/Auth/PasswordConfirmationTest.php` | 3 | confirm screen, confirmed, wrong password |
| `Feature/ProfileTest.php` | 5 | profile page, email update, verified flag, delete, wrong password |
| `Feature/ExampleTest.php` | 1 | `GET /` returns 200 |
| `Unit/ExampleTest.php` | 1 | `assertTrue(true)` |

Both `ExampleTest` files are the framework defaults. `Feature/ExampleTest` has its
`RefreshDatabase` call commented out. `composer test` runs
`php artisan config:clear` first, then `php artisan test`.

## Consequences

### Positive

- A green suite proves the auth layer works, and it is the layer where a regression is
  most likely to lock everyone out.
- The test environment is fully isolated (`:memory:` SQLite, array cache/session/queue,
  `MAIL_MAILER=array`) so tests are fast and leave no residue.
- `BCRYPT_ROUNDS=4` keeps auth tests fast enough to run on every save.
- The two-suite structure is already in place, so adding the first application test is
  a matter of dropping a file into `tests/Feature/`.

### Negative

- **Every defect in [technical-debt.md](../technical-debt.md) is a defect a test would
  have caught.** There is no test for `Api\RecordController::store()` — which is dead on
  every request — for `Api\MeadowController` — which issues 61 queries per page — or for
  `RecordResource` — which emits a key with a trailing space and leaks emails publicly.
  A single test per endpoint would surface the lot.
- **The green suite is actively misleading.** 23 passing tests read as "the app is
  tested" when 100% of them exercise framework code. This is the most expensive
  consequence: the signal people rely on does not exist.
- **No test can run against MySQL**, so the environment the app actually runs in is
  never exercised ([ADR-0012](0012-mysql-as-the-primary-datastore.md)).
- `tests/Feature/ExampleTest.php` has `RefreshDatabase` commented out — a one-line
  leftover that will confuse the next person.
- `tests/Unit/ExampleTest.php` asserts `true === true`. It passes and tests nothing.
- There is no CI configuration in the repository, so nothing runs the suite
  automatically.

### Neutral / follow-on

- [ADR-0005](0005-laravel-breeze-as-the-web-auth-baseline.md) — the source of these
  tests.
- [ADR-0012](0012-mysql-as-the-primary-datastore.md) — the database mismatch that makes
  `DashboardController` untestable today.

## Drift

- **No test exercises `GET /api/dashboard/stats`, so the MySQL-only `DATE_FORMAT` call
  is untested.** The suite runs on SQLite, where the endpoint throws. This is the
  clearest example of the test-environment mismatch hiding a real defect.
- **No test exercises image upload or deletion**, so the four
  `Storage::disk('records')` calls, the `hasFile('iamge')` typo and the
  `if ($record->image)` check all went unnoticed.
- **No test asserts the shape of any API response**, so the `'category '` trailing-space
  key and the enum-object serialization have no failing test pointing at them.
- **No test asserts query counts**, so the N+1 problems in the Meadow, the Prism and the
  admin record list are invisible.
