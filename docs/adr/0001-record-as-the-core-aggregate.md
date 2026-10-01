# 0001. Record as the core aggregate

- **Status:** Accepted
- **Date:** 2026-09-15
- **Area:** Domain
- **Affects:** `app/Models/Record.php`, `database/migrations/2026_09_15_101252_create_records_table.php`

## Context

Bloom is a journaling and milestone-tracking product. A user writes up something that
happened to them, dates it, and classifies it along three independent axes: what kind
of achievement it was (Category), how big it was (Tier), and how it made them feel
(Emotion).

The obvious alternative shape is a single flat `entries` table with string columns —
`category varchar`, `tier varchar`, `emotions varchar`. That is cheaper to build and
would have been fine for a one-user diary.

But two of Bloom's stated features make free text unusable. The dashboard aggregates
records by category and by tier over time ([ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md)),
and the Blooming Meadow filters a public feed by category and by emotion. Both need
grouping and joining over the same values. A `varchar` column forces either a
`GROUP BY category` that fragments on whitespace and casing, or an application-side
lookup table that has to be built anyway.

## Decision

We made **`Record` the single core aggregate**, and modelled the three classification
axes as separate tables that `Record` references:

- `records.category_id` → `categories` (nullable FK)
- `records.tier_id` → `tiers` (nullable FK)
- `emotions` via the `emotion_record` pivot ([ADR-0007](0007-many-to-many-emotions-via-an-explicit-pivot.md))

`Record` is the **only** model in the system with behaviour on it: it casts
`visibility` to an enum and `date` to a Carbon instance
([ADR-0008](0008-visibility-as-a-cast-enum.md)). `Category`, `Tier`, `Emotion` and
`UserProfile` are pure data holders with relationships and nothing else.

`Record` is owned by exactly one `User` (`records.user_id`), which is what makes the
private data story tractable: "my records" is a single `hasMany`, and the
Blooming Meadow is "every record where `visibility = 'public'`".

## Consequences

### Positive

- Aggregations in the dashboard are plain `JOIN` + `GROUP BY`, not string munging.
- Adding a category or tier is an `INSERT`; no code change, no redeploy.
- `Record` is the single place ownership, visibility and upload concerns live, so
  there is exactly one place to reason about them.
- The API can expose one resource type (`RecordResource`) for four different surfaces.

### Negative

- Every read of a record that needs a label pays a join. Eager loading is therefore
  not optional, and forgetting it is a live performance problem — see *Drift*.
- A record with a null `category_id` or `tier_id` is representable and, because the
  FKs are nullable, is silently allowed. The API's Form Request requires both, but the
  backoffice does not.
- The taxonomy tables are admin-managed through five near-identical CRUD controllers
  for what is conceptually one "manage vocabulary" feature.

### Neutral / follow-on

- [ADR-0006](0006-normalized-taxonomies-with-nullable-foreign-keys.md) — why the FKs are
  nullable columns and not enums.
- [ADR-0007](0007-many-to-many-emotions-via-an-explicit-pivot.md) — why Emotion is the
  one axis that is many-to-many.
- [ADR-0010](0010-ownership-checks-in-controllers-instead-of-policies.md) — ownership
  follows from `records.user_id`.

## Drift

- **The `user_id` foreign key has no on-delete behaviour, and the intent to remove it
  was silently discarded.** The migration
  `2026_09_21_150015_add_user_id_column_to_records_table.php` calls
  `->constrained()->dropForeign()` in the same `up()` chain. `ForeignIdColumnDefinition`
  has no `dropForeign()` method, so that call falls through to `Fluent::__call` and only
  records an attribute — the `foreign` command is registered normally. The constraint
  **is** created, with no `ON DELETE` clause. Separately, the `down()` method cannot
  reverse it on SQLite, which is what the test suite runs on. The practical consequence
  is that `Admin\UserController::destroy()` throws an integrity violation for any user
  who owns records. See [td-09](../technical-debt.md#td-09).
- **`records.user_id` is nullable and the backoffice never sets it.**
  `Admin\RecordController::store()` creates a record without assigning `user_id`
  (`app/Http/Controllers/Admin/RecordController.php:45-73`). Records created through
  `/admin/records` therefore have no owner, are invisible to
  `$user->records()`, and cannot be updated or deleted through the API.
- **Eager loading is inconsistently applied**, which makes it the single biggest
  performance risk in the system. `RecordResource` reads `category`, `tier`, `emotions`
  *and* `user`, so any query that returns records without all four eager-loaded is
  N+1. `Api\RecordController::index()` loads three of the four;
  `Api\MeadowController` and `Api\PrismController` load one each.
