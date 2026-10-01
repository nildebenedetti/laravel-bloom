# 0014. Hand-built analytics endpoints for the dashboard

- **Status:** Accepted
- **Date:** 2026-09-23
- **Area:** API
- **Affects:** `app/Http/Controllers/Api/DashboardController.php`, `routes/api.php`, `resources/views/dashboard.blade.php`

## Context

Bloom's private suite has a dashboard that answers three questions about the user's own
records:

1. **Spider chart** — which emotions appear, and how often? A radar chart, one axis per
   emotion, so the fixed set of emotions is meaningful as a shape.
2. **Pie chart** — how are records distributed across categories?
3. **Area chart** — a velocity timeline: how many records per month, stacked by tier,
   so growth over time is visible.

Recharts (in the SPA) renders all three. The question this ADR answers is where the
aggregation and reshaping happens: in the database, in Laravel, or in the browser.

We rejected doing it in the browser. Fetching every record and counting in JavaScript
would work at 10 records and fall over at 10,000, and it would move private data into
the client for no reason. Server-side aggregation was the only serious option.

## Decision

We added a **single endpoint, `GET /api/dashboard/stats`**, behind `auth:sanctum`, that
returns all three datasets in one response. It is the only endpoint in the API that does
**not** use `RecordResource` ([ADR-0009](0009-json-api-shaped-api-resources.md)) — it
returns `response()->json()` directly, because it returns aggregates, not records.

**Time range** is a single `time_range` query parameter with three values, applied as a
date filter on `records.date`:

| `time_range` | Filter |
| --- | --- |
| `all_time` (default) | none |
| `last_month` | `date >= now()->subMonth()` |
| `last_six_months` | `date >= now()->subMonths(6)` |

**Each chart is its own query**, built on a shared base so they cannot drift apart:

```php
$baseQuery = $user->records();          // a HasMany relation, pre-scoped to the owner

$spiderChartData = DB::table('emotions')
    ->join('emotion_record', 'emotions.id', '=', 'emotion_record.emotion_id')
    ->join('records', 'records.id', '=', 'emotion_record.record_id')
    ->where('records.user_id', '=', $user->id)
    ->select('emotions.name as emotion', DB::raw('count(records.id) as count'))
    ->groupBy('emotions.id', 'emotions.name')
    ->get();

$pieChartBaseQuery = clone $baseQuery;   // clone, so the joins don't leak across charts
$pieChartData = $pieChartBaseQuery
    ->join('categories', 'categories.id', '=', 'records.category_id')
    ->select('categories.name as category', DB::raw('count(*) as count'))
    ->groupBy('category')
    ->get();

$areaChartData = /* see below */;
```

**The area chart's reshaping happens in PHP, not SQL.** The query returns one row per
`(month, tier)` pair, and the controller folds it into the wide format Recharts needs
for a stacked area — one object per month, one `tier_<id>` key per tier:

```php
$rawTimeline = $baseQuery
    ->join('tiers', 'tiers.id', '=', 'records.tier_id')
    ->select(
        DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
        'tiers.id as tier',
        DB::raw('count(*) as count')
    )
    ->groupBy('month', 'tier')
    ->orderBy('month', 'asc')
    ->get();

$timelineGrouped = [];
foreach ($rawTimeline as $row) {
    if (! isset($timelineGrouped[$row->month])) {
        $timelineGrouped[$row->month] = ['month' => $row->month];
    }
    $timelineGrouped[$row->month]['tier_' . $row->tier] = (int) $row->count;
}
$areaChartData = array_values($timelineGrouped);
```

`array_values()` drops the string month keys and re-indexes to a sequential list, which
is what a Recharts `data` array must be.

Response shape:

```json
{
  "time_range": "last_six_months",
  "charts": {
    "spider": [{ "emotion": "Proud", "count": 4 }],
    "pie":    [{ "category": "Career", "count": 7 }],
    "area":   [{ "month": "2026-07", "tier_1": 2, "tier_2": 5 }]
  }
}
```

`emotion` and `category` are SQL aliases; `tier_<id>` keys are built in PHP.

## Consequences

### Positive

- One round trip for the whole dashboard instead of three.
- The payload is tiny and pre-shaped for the chart library, so the SPA has no reshaping
  logic and no dependency on the full record set.
- The date filter is applied in SQL, so the row count does not grow with history.
- Each chart is a separate query, so a slow chart can be profiled and optimised
  independently.
- The SQL for each chart is written out in the source as a comment block, which makes
  the generated SQL reviewable without a debugger.

### Negative

- **The endpoint is presentation-aware.** `tier_<id>` keys and `%Y-%m` month strings
  encode Recharts' expectations into the backend. A different chart library, or a
  different breakdown, means changing PHP.
- **The `tier_<id>` key naming is opaque and not self-describing.** The client cannot
  render an axis label from `tier_3`; it must resolve tier ids to names from somewhere
  else. The response contains no tier names.
- **The spider chart's fixed shape is implicit.** Recharts renders a polygon whether or
  not an emotion has any records, so an emotion with zero occurrences is absent from the
  response and produces a gap in the radar. There is no zero-filling.
- **The base query is a relation, not a builder.** `$baseQuery = $user->records()` returns
  a `HasMany`, and `->join()` mutates that instance. Correctness depends on the `clone`
  discipline, which is a convention a reader has to notice rather than a guarantee the
  type system enforces.
- **Timestamps in the response are strings from the database**, not ISO-8601 Carbon
  instances, and there is no timezone contract.
- Three independent queries with no transaction: the three datasets can be mutually
  inconsistent if a record is created between them.

### Neutral / follow-on

- [ADR-0006](0006-normalized-taxonomies-with-nullable-foreign-keys.md) — the `categories`
  and `tiers` joins.
- [ADR-0007](0007-many-to-many-emotions-via-an-explicit-pivot.md) — the
  `emotion_record` join behind the spider chart.
- [ADR-0012](0012-mysql-as-the-primary-datastore.md) — why the tests cannot run this
  code.

## Drift

- **`DATE_FORMAT` is MySQL-only and the test suite is SQLite.** On SQLite this endpoint
  throws `no such function: DATE_FORMAT`. There is no test for it
  ([ADR-0017](0017-phpunit-feature-tests-from-the-breeze-baseline.md)).
- **`groupBy('category')` groups by a select alias.** MySQL allows this; PostgreSQL
  rejects it. It should be `->groupBy('categories.id', 'categories.name')`, matching how
  the spider query does it.
- **`$areaChartBaseQuery` is assigned and then never used.** Line 78 clones the base
  query into `$areaChartBaseQuery`; line 80 then uses `$baseQuery` instead. The clone is
  dead code. It happens to be harmless — it is the last query built, so the mutation
  goes nowhere — but it means the one place the `clone` discipline would have mattered
  is where it was forgotten.
- **The spider query duplicates the time filter instead of inheriting it.** It starts
  from `DB::table('emotions')` and re-implements both `when()` branches, rather than
  sharing the same filter construction as `$baseQuery`. Three copies of the same
  date-window logic.
- **The `time_range` parameter is never validated.** Any unrecognised value silently
  behaves as `all_time`, because the `if/elseif` chain has no `else`. There is no Form
  Request ([ADR-0013](0013-form-requests-as-the-validation-boundary.md)) and no
  `Rule::in` for it.
- **`resources/views/dashboard.blade.php` is a server-rendered page that is not the
  dashboard.** The Blade view at `/dashboard` is a landing page; the chart dashboard
  lives in the SPA and is reached through `GET /api/dashboard/stats`. The route name
  `dashboard` and the analytics endpoint share a name but not a surface.
