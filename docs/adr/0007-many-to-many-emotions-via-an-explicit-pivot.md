# 0007. Many-to-many emotions via an explicit pivot

- **Status:** Accepted
- **Date:** 2026-09-17
- **Area:** Data
- **Affects:** `app/Models/Emotion.php`, `app/Models/Record.php`, `database/migrations/2026_09_17_144528_create_emotion_record_table.php`

## Context

`Category` and `Tier` classify a record along one axis each, so a nullable FK per axis
was enough ([ADR-0006](0006-normalized-taxonomies-with-nullable-foreign-keys.md)).
Emotion does not work that way. A single milestone is usually felt several ways at
once: finishing a degree is *Proud*, *Relieved* and *Determined* simultaneously. The
product's own copy calls the emotion view the "Prism" for this reason — one record
refracts into several colours.

So Emotion needs a genuine many-to-many. Three options:

| Option | Store | Trade-off |
| --- | --- | --- |
| `emotion_record` pivot | `belongsToMany` | Standard; needs a pivot table |
| JSON column on `records` | `emotions` json | Simple writes; no FK integrity, no index, cannot `JOIN` for the dashboard |
| EAV (generic `tags` + `taggables`) | two tables | Maximum flexibility; two extra joins on every read, and a taxonomy of *what can be tagged* is a whole subsystem |

## Decision

We modelled Emotion as a **`belongsToMany` on `Record`**, backed by an explicitly
migrated pivot table named `emotion_record` (singular, not Laravel's usual plural
`emotion_record` — it happens to coincide with the convention, so no table name had to
be passed to the relationship):

```php
// app/Models/Record.php
public function emotions() { return $this->belongsToMany(Emotion::class); }
```

```php
// 2026_09_17_144528_create_emotion_record_table.php
Schema::create('emotion_record', function (Blueprint $table) {
    $table->id();
    $table->foreignId('emotion_id')->constrained()->cascadeOnDelete();
    $table->foreignId('record_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
});
```

`emotions` carries a `color` (7-char hex) alongside `name`, so the SPA can render the
Prism and the Meadow chips without a colour lookup:

```php
class Emotion extends Model
{
    protected $fillable = ['name', 'color'];

    public function records() { return $this->belongsToMany(Record::class); }
}
```

Seeded with 7 values in `EmotionsTableSeeder`: Proud, Relieved, Excited, Determined,
Grateful, Grounded, Cherished — each given a random hex colour.

**Writes use `attach` for create and `sync` for update.** The API's `store()` uses
`attach()` and `update()` uses `sync()`; the backoffice mirrors this and additionally
calls `detach()` when no emotions are submitted, so clearing the checkbox clears the
pivot. Filtering uses `whereHas` + `whereIn` on the relation, which compiles to an
`EXISTS` subquery rather than a `JOIN` — so a record matching two requested emotions
is returned once, not twice.

## Consequences

### Positive

- A record can carry any number of emotions with referential integrity on both sides.
- `cascadeOnDelete()` on both FKs means deleting an emotion or a record cleans up the
  pivot automatically. Nothing in application code has to.
- `whereHas` filtering is a single `EXISTS`, so the Meadow and Prism filters cannot
  produce duplicate rows.
- The dashboard's spider chart is a three-table join off this pivot
  ([ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md)).

### Negative

- **Eager loading `emotions` is required on every record-listing endpoint**, adding a
  query plus a result-transform step per page. `Api\PrismController` uses
  `->load('emotions')` on the paginator *after* paginating, which works only because
  `AbstractPaginator` forwards `__call` — a fragile contract to rely on.
- **Write semantics differ between create and update** (`attach` vs `sync`), and the
  difference matters: `attach` would duplicate pivot rows on a second call. There is no
  unique index on `(emotion_id, record_id)`, so nothing prevents duplicates at the
  database level.
- The pivot carries `timestamps()` that nothing maintains on `sync()` — pivot rows
  silently keep their original `created_at`.
- `Emotion` has no `HasFactory`, so `RecordFactory`'s `afterCreating` hook attaches
  emotions correctly but the factory cannot create one on demand.

### Neutral / follow-on

- [ADR-0001](0001-record-as-the-core-aggregate.md) — the aggregate this axis belongs to.
- [ADR-0009](0009-json-api-shaped-api-resources.md) — `EmotionResource` nesting.

## Drift

- **No unique constraint on the pivot.** `emotion_record` has an auto-increment `id`
  and nothing preventing `(emotion_id, record_id)` from appearing twice. Only
  application code (`sync`) keeps it clean.
- **`Api\PrismController` calls `->load('emotions')` on a paginator, not on the query
  builder** (`app/Http/Controllers/Api/PrismController.php:27`). It happens to work
  because `AbstractPaginator::__call` proxies to `$this->items`, but the resource also
  reads `category`, `tier` and `user`, none of which are loaded — so the endpoint is
  an N+1 generator too.
- **The pivot `created_at`/`updated_at` are not maintained** by `sync()`; only the
  insert-time value is meaningful.
- **`emotion_record` is named in the singular** while `belongsToMany` conventionally
  derives a plural name. This works only because the two happen to collide for
  `Emotion` + `Record`. If either model is ever renamed, the relationship will start
  looking for a table that does not exist, with no error at the point of the rename.
