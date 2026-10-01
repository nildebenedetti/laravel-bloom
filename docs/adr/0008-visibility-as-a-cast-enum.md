# 0008. Visibility modelled as a cast enum

- **Status:** Accepted
- **Date:** 2026-09-15
- **Area:** Data
- **Affects:** `app/Enums/RecordVisibility.php`, `app/Models/Record.php`, `resources/views/records/create.blade.php`

## Context

Every record is either shared with the world or kept to its owner. This single flag
drives the two most important behaviours in the product:

- the **Blooming Meadow**, a public feed that reads `visibility = 'public'`
  (`app/Http/Controllers/Api/MeadowController.php:14`)
- **privacy itself** — a mistake here leaks a user's private journal to anonymous
  visitors

It is also the only place in the schema where a domain rule and a storage constraint
had to be kept in sync by hand, and where a default matters: a record created without
an explicit visibility must be private, not public.

## Decision

We modelled visibility in **three places that must agree**, and cast it to a PHP enum so
application code never handles the raw string.

**1. The database column** — a native `enum`, defaulting to `private`:

```php
$table->enum('visibility', ['public', 'private'])->default('private');
```

**2. A string-backed enum** — `app/Enums/RecordVisibility.php`:

```php
enum RecordVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public function label() {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
        };
    }
}
```

**3. The model cast** — so the DB value arrives as an enum instance and the enum
instance is written back as its string:

```php
protected function casts(): array
{
    return [
        'visibility' => RecordVisibility::class,
        'date'       => 'date',   // Carbon
    ];
}
```

**Write paths coerce defensively.** The backoffice (`Admin\RecordController::update:118`)
uses `tryFrom()` with a null-coalescing fallback so an unrecognised or missing value can
never throw a `ValueError` and never defaults to public:

```php
$data['visibility'] = $record->visibility =
    RecordVisibility::tryFrom($request->input('visibility')) ?? RecordVisibility::PRIVATE;
```

The API path validates instead: `StoreRecordRequest` requires
`'visibility' => ['required', 'string', Rule::in(['public', 'private'])]`.

`label()` exists for the Blade form, which renders a select of `Public` / `Private`.

## Consequences

### Positive

- `$record->visibility === RecordVisibility::PUBLIC` is a compile-time-checked
  comparison. A typo in a raw string is a fatal, not a silent wrong answer.
- `case` gives exhaustive `match` handling, so adding a third state surfaces every
  place that must handle it.
- The DB `enum` is a second line of defence: even a direct `DB::insert` cannot store
  an unsupported value.
- `default('private')` makes the safe outcome the default outcome at the storage layer,
  not just in application code.

### Negative

- **Three places to update when a state is added.** Migration (on MySQL this is
  `ALTER TABLE`, not just a new insert value), enum, and every `match`/`Rule::in`.
- **MySQL and SQLite disagree about enum enforcement.** MySQL honours
  `ENUM('public','private')` strictly. SQLite has no enum type — the schema builder
  emits a `varchar` with a `CHECK` constraint instead. `phpunit.xml` runs tests on
  SQLite `:memory:`, so **enum enforcement is not exercised by the test suite**, while
  the application runs on MySQL (`DB_CONNECTION=mysql` in `.env`). This divergence is
  the direct cause of the `DATE_FORMAT` problem in
  [ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md).
- **The API rejects a well-formed `PATCH` that omits `visibility`**, because
  `StoreRecordRequest` marks it `required` and is reused for update
  ([ADR-0013](0013-form-requests-as-the-validation-boundary.md)).
- **The enum's boundaries are lost in transit.** Laravel's serializer unwraps the
  `BackedEnum` to its backing value, so `RecordResource` emits `"visibility": "public"`,
  indistinguishable from the raw column. The client cannot discover the valid set from
  the response, and `label()` exists for the Blade form only. See
  [ADR-0009](0009-json-api-shaped-api-resources.md).

### Neutral / follow-on

- [ADR-0001](0001-record-as-the-core-aggregate.md) — the aggregate that owns it.
- [ADR-0009](0009-json-api-shaped-api-resources.md) — how it reaches the client.

## Drift

- **The two write paths do not share the enum.** The backoffice uses
  `RecordVisibility::tryFrom(...)`, the API uses `Rule::in(['public', 'private'])` with
  two hand-typed strings. A third state added to the enum would be enforced in one path
  and silently rejected in the other.
- **The Meadow's filter compares against a raw string, not the enum** —
  `Record::where('visibility', 'public')` in `MeadowController:14`. It works because
  the enum's backing value happens to be `'public'`, but it is a magic string outside
  the enum.
- **`Admin\RecordController::store()` assigns the raw string from the request with no
  coercion at all** (`$newRecord->visibility = $data['visibility'];`, line 52). Combined
  with the total absence of validation on that path, a crafted form post writes
  whatever string the client sends. MySQL's `enum` column is the only thing stopping
  it; this would succeed silently on SQLite.
