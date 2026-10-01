# 0016. Migrations as the schema source of truth

- **Status:** Accepted
- **Date:** 2026-09-15
- **Area:** Data
- **Affects:** `database/migrations/`, `database/seeders/`, `database/factories/`

## Context

Bloom's schema was built incrementally over about a week, one entity at a time, mostly
along dedicated branches (`1-crud---records-main-entity`, `2-crud---categories`,
`3-crud---tiers`, `4-crud---emotions`, `18-crud---user`). Each branch added a table and
then a separate follow-up migration to add a foreign key to `records`, because the
referenced table did not exist when the first migration was written.

Laravel offers two ways to define a schema: **migrations** (executable PHP, versioned,
diffable) and a **schema dump** (`php artisan schema:dump`, a single SQL file, plus
seeders). The framework's default is migrations only; `schema:dump` is opt-in and is
commonly used to speed up CI on large databases.

Bloom is a small database with fourteen migrations. Dumping the schema would save
nothing and would create a second source of truth.

## Decision

We define the schema **exclusively through migrations**, committed to the repository,
with no schema dump. The full set, in order:

| Migration | Effect |
| --- | --- |
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_09_15_101252_create_records_table` | `records` |
| `2026_09_16_083953_add_alt_text_column_to_records_table` | `records.image_alt` |
| `2026_09_16_140053_create_categories_table` | `categories` |
| `2026_09_16_140849_add_category_id_to_records_table` | `records.category_id` + FK |
| `2026_09_17_075539_create_tiers_table` | `tiers` |
| `2026_09_17_083220_add_tier_column_to_records_table` | `records.tier_id` + FK |
| `2026_09_17_141021_create_emotions_table` | `emotions` |
| `2026_09_17_144528_create_emotion_record_table` | `emotion_record` pivot |
| `2026_09_18_134648_add_role_column_to_users_table` | `users.role` |
| `2026_09_18_135137_create_user_profiles_table` | `user_profiles` |
| `2026_09_21_085509_create_personal_access_tokens_table` | `personal_access_tokens` |
| `2026_09_21_150015_add_user_id_column_to_records_table` | `records.user_id` (FK dropped) |

Data is loaded through **`DatabaseSeeder`**, which calls five seeders in dependency
order — `UserTableSeeder` → `CategoriesTableSeeder` → `TiersTableSeeder` →
`EmotionsTableSeeder` → `RecordsTableSeeder` — using `WithoutModelEvents`. Records are
generated with Faker (title from 4 words, description from 4 paragraphs, a random date
within the last year, a 50/50 `public`/`private` visibility via `rand(0, 1)`, a random
category and tier, and 2–5 random emotions). The seeder creates two users with fixed
credentials: `admin@bloom.org` (role `admin`) and `offHell@live.com` (role `user`).

Two factories exist: `UserFactory` and `RecordFactory`
([ADR-0006](0006-normalized-taxonomies-with-nullable-foreign-keys.md)).

## Consequences

### Positive

- `php artisan migrate` on an empty database reproduces the schema exactly. There is
  nothing to interpret and no file to keep in sync.
- Migration history is a readable, chronological record of how the schema came to be —
  including that `category_id` was added *after* `categories` existed.
- Every `down()` is written, so migrations are reversible.
- Seeder order makes the FKs satisfiable, and `RecordsTableSeeder` reads real ids back
  with `Emotion::pluck('id')` rather than assuming them.

### Negative

- **Sixteen migrations for seven tables**, because FKs had to be added after their
  referenced tables. A fresh install runs all of them.
- **The `records.user_id` migration is not reversible on SQLite** — its `down()` calls
  `dropForeign('records_user_id_foreign')`, and `SQLiteGrammar::compileDropForeign`
  throws `This database driver does not support dropping foreign keys by name`. Since
  the test suite runs on SQLite, this cannot be exercised locally. The same call appears
  in the `add_category_id` and `add_tier_column` migrations. Meanwhile the matching
  `->dropForeign()` in the `up()` chain is a silent no-op (there is no such method on
  `ForeignIdColumnDefinition`), so the constraint it appears to remove is in fact
  created. The migration therefore has the worst of both: the write it intended does
  nothing, and the write it did do cannot be undone.
- **Seeders hardcode foreign keys as integer ranges.** `RecordsTableSeeder` uses
  `rand(1, 12)` for `category_id` and `rand(1, 4)` for `tier_id`, assuming the seeders
  above them produced exactly 12 and 4 rows. Adding a 13th category and re-seeding
  silently reassigns nothing today (12 is still the count) but reordering the arrays
  would attach records to the wrong labels.
- **The seeders contain live credentials** — `admin@bloom.org` /
  `safepsw@bloom2026` and `offHell@live.com` / `password123` — committed in
  plaintext. `WithoutModelEvents` means no `PasswordReset` notification fires, and the
  `hashed` cast means they are stored correctly as hashes, but the passwords are public
  knowledge. These are development accounts and must never exist in a production seed.
- **The committed `database/database.sqlite` is a leftover from a SQLite-era migration**
  and is not kept in sync with the migrations
  ([ADR-0012](0012-mysql-as-the-primary-datastore.md)).

### Neutral / follow-on

- [ADR-0006](0006-normalized-taxonomies-with-nullable-foreign-keys.md) — the two tables
  whose column widths are inconsistent.
- [ADR-0012](0012-mysql-as-the-primary-datastore.md) — the engine these migrations run on.
