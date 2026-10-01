# 0006. Normalized taxonomies with nullable foreign keys

- **Status:** Accepted
- **Date:** 2026-09-16
- **Area:** Data
- **Affects:** `app/Models/Category.php`, `app/Models/Tier.php`, `database/migrations/2026_09_16_140053_create_categories_table.php`, `database/migrations/2026_09_17_075539_create_tiers_table.php`

## Context

`Category` and `Tier` are closed vocabularies that the admin backoffice edits at
runtime ([ADR-0004](0004-blade-backoffice-for-administration.md)) and that the
dashboard aggregates by ([ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md)).

Bloom could model them as:

| Option | Adding a value | Aggregating |
| --- | --- | --- |
| PHP enum + string column | code change + deploy + migration | `GROUP BY tier` over free text — fragments on casing/whitespace |
| `string` column on `records` | free text at write time, no admin screen | unusable |
| **Lookup table + FK** | admin `INSERT` | `JOIN` + `GROUP BY id` — exact |

The tradeoff with a lookup table is that a value now has a *row* and a *name*, and
anything that displays it must join to get the label. That join is a real cost paid on
every read.

## Decision

We created **`categories`** and **`tiers`** as thin lookup tables, referenced from
`records` by **nullable** foreign key columns added in follow-up migrations.

```php
// categories                              // tiers
$table->id();                              $table->id();
$table->string('name', 80);                $table->string('name', 80);
$table->string('description', 255);        $table->string('description', 255)->nullable();
$table->timestamps();                      $table->timestamps();

// records (add_category_id migration)
$table->foreignId('category_id')->nullable()->constrained();
```

Both models are deliberately minimal — no casts, no scopes, no `HasFactory`:

```php
class Category extends Model
{
    protected $fillable = ['name', 'description'];

    public function records() { return $this->hasMany(Record::class); }
}
```

The taxonomy is seeded in `database/seeders/CategoriesTableSeeder.php` (12 values:
Career, Studies, Bonds, Sports, Cooking, Crafting, Wellness, Travel, Finance,
Languages, Culture, Promises) and `TiersTableSeeder.php` (4 values: small win, solid
step, major milestone, epic breakthrough).

## Consequences

### Positive

- The dashboard's `GROUP BY` is exact and joins on integer keys.
- Values are added and renamed by an admin without a deploy.
- Descriptions are available for tooltips and for the SPA's filter UI without a
  second lookup.
- Downstream joins are cheap: integer FK to integer PK, with the FK indexes created
  by `constrained()`.

### Negative

- **Every read that shows a label pays a join.** This is the direct cause of the N+1
  queries documented in `technical-debt.md` — `RecordResource` dereferences
  `$this->category?->name` and `$this->tier?->name`, so those relations must be
  eager-loaded or the endpoint is slow.
- **The `name` column is not unique.** Nothing prevents "Career" and "career" from
  coexisting, which would silently split an aggregation group. The unique index is also
  *not* enforced at the form layer — the admin `CategoryController` has no validation at
  all ([ADR-0013](0013-form-requests-as-the-validation-boundary.md)).
- **Values are hardcoded in the seeders.** They look like constants but are mutable
  rows, and nothing prevents an admin from deleting a `tier_id` that the SPA or the
  dashboard assumes exists. The seeders' `rand(1, 12)` / `rand(1, 4)` in
  `RecordsTableSeeder` encode the current counts as magic numbers, so reordering or
  trimming the seed data silently reassigns records to the wrong labels.
- Neither model uses `HasFactory`, so `Category::factory()` does not exist — which
  makes `RecordFactory` fail if its fallback branch runs. See *Drift*.

### Neutral / follow-on

- [ADR-0001](0001-record-as-the-core-aggregate.md) — the aggregate these belong to.
- [ADR-0007](0007-many-to-many-emotions-via-an-explicit-pivot.md) — why Emotion took a
  different relational approach.

## Drift

- **`RecordFactory` calls `Category::factory()` and `Tier::factory()`, which do not
  exist.** `database/factories/RecordFactory.php:27-28` falls back to
  `Category::inRandomOrder()->first()?->id ?? Category::factory()->create()->id`. Only
  `UserFactory` and `RecordFactory` exist in `database/factories/`, and only `User` and
  `Record` models use the `HasFactory` trait — so the fallback branch throws
  `BadMethodCallException` ("Call to undefined method"). The factory is therefore only
  usable against a database already populated with categories and tiers.
- **`tiers.description` is nullable but `categories.description` is not**, an
  inconsistency between two structurally identical tables. The API's
  `StoreRecordRequest` does not validate descriptions at all, so this only matters to
  the admin forms.
