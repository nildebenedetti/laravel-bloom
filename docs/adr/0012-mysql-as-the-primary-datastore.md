# 0012. MySQL as the primary datastore

- **Status:** Accepted
- **Date:** 2026-09-15
- **Area:** Infrastructure
- **Affects:** `.env`, `.env.example`, `phpunit.xml`, `app/Http/Controllers/Api/DashboardController.php`

## Context

Bloom's read patterns are aggregate-and-group: counts of records per emotion, per
category, and per tier per month. The dashboard endpoint
([ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md)) does three
`GROUP BY` queries over a three-table join. None of this is exotic, but two of the
queries lean on the database for work that another engine would not do the same way.

The choice was between MySQL (via the local server the project was developed against),
SQLite (Laravel's default, and what `phpunit.xml` uses), and PostgreSQL.

## Decision

We run the application on **MySQL 8** on localhost, and run the **test suite on SQLite
in-memory**. These are two different databases on purpose, and the mismatch is
documented here because it has consequences.

- `.env` (not committed; mirrored in `.env.example` with SQLite as the shipped default):
  ```
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=laravel-bloom
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- `phpunit.xml`:
  ```xml
  <env name="DB_CONNECTION" value="sqlite"/>
  <env name="DB_DATABASE" value=":memory:"/>
  ```

The two databases agree on everything Bloom's schema needs: `enum`, `foreignId`
constraints, `json`-free columns, the `emotion_record` pivot. They do **not** agree on
date formatting or aggregate aliasing, which is where the cost shows up.

A `database/database.sqlite` file is also committed, from an earlier SQLite-era
migration. It is not used by either the current `.env` or the test suite.

## Consequences

### Positive

- MySQL is what the app was actually developed and demonstrated against, so
  `DATE_FORMAT` works and the dashboard charts render.
- SQLite `:memory:` makes the test suite fast and dependency-free — no server to start,
  and each test gets a fresh database.
- `enum` columns give real type enforcement in production
  ([ADR-0008](0008-visibility-as-a-cast-enum.md)).
- `cascadeOnDelete()` on the pivot FKs is honoured by both engines.

### Negative

- **The test suite cannot execute the code it is supposed to protect.** The dashboard
  uses `DATE_FORMAT(date, '%Y-%m')`, a MySQL-only function. On SQLite it throws
  `SQLSTATE[HY000]: General error: 1 no such function: DATE_FORMAT`. There is no test
  for `DashboardController::stats`, so this has never been caught.
- **The enum column is enforced differently.** On MySQL, `ENUM('public','private')`
  rejects a bad value at the database. On SQLite the schema builder emits a `varchar`
  plus a `CHECK`, so a code path that writes raw strings
  (`Admin\RecordController::store()`) is protected in production and not in tests. The
  test suite is therefore systematically more permissive than production.
- **Grouping by a select alias is MySQL-specific.** `DashboardController:66` does
  `->groupBy('category')` where `category` is `categories.name as category`. MySQL
  permits it; PostgreSQL rejects it with "column \"category\" does not exist". This is a
  latent portability bug that SQLite does not catch either.
- **Two sources of truth for the schema.** `database/database.sqlite` is committed and
  drifting; anyone who opens it sees a schema that may not match the migrations.
- `.env.example` ships `DB_CONNECTION=sqlite` while `.env` uses MySQL, so a new
  developer who copies the example file gets a different database engine from the one
  the app was written against — and will not notice until a MySQL-only function fails.

### Neutral / follow-on

- [ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md) — the queries
  that depend on this choice.
- [ADR-0017](0017-phpunit-feature-tests-from-the-breeze-baseline.md) — why the mismatch
  was never surfaced.
