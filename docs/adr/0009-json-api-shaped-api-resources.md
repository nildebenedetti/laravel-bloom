# 0009. JSON:API shaped API resources

- **Status:** Accepted
- **Date:** 2026-09-22
- **Area:** API
- **Affects:** `app/Http/Resources/RecordResource.php`, `app/Http/Resources/EmotionResource.php`, `app/Traits/HttpResponse.php`

## Context

Because the SPA is a separate repository ([ADR-0002](0002-decoupled-architecture-with-a-separate-spa.md)),
the JSON response shape *is* the contract between two codebases that cannot see each
other. Without a deliberate serialization boundary, controllers leak Eloquent models
directly into JSON, and any column rename becomes a production incident.

Laravel's `JsonResource` is the mechanism for preventing that, but it leaves the shape
to the author. We had to pick a shape.

The competing considerations:

- **A flat shape** (`{id, title, category, tier, emotions[]}`) is easiest for a React
  client to consume — no nesting, direct property access.
- **A `attributes`/`relationships` split** (JSON:API-inspired) makes it explicit which
  keys are record fields and which are resolved relations, so a client can distinguish
  "the field is absent" from "the relation is not loaded".
- **An `envelope`** (`{status, message, data}`) is common in Laravel tutorials and easy
  to post-process globally, but it makes every client call `response.data.data`.

Bloom's records have four relations of different cardinality, which is the case where
the `attributes`/`relationships` split earns its keep.

## Decision

We created **two API Resources** and return them from every record-listing and
record-single endpoint.

**`RecordResource`** uses a JSON:API-inspired envelope with IDs stringified, attributes
and relationships separated:

```php
return [
    'id' => (string) $this->id,
    'attributes' => [
        'title'       => $this->title,
        'description' => $this->description,
        'date'        => $this->date,
        'image_path'  => $this->image_path,
        'image_alt'   => $this->image_alt,
        'category '   => $this->category?->name,
        'tier'        => $this->tier?->name,
        'visibility'  => $this->visibility,
        'emotions'    => EmotionResource::collection($this->whenLoaded('emotions')),
    ],
    'relationships' => [
        'user' => [
            'id'         => (string) $this->user?->id,
            'user name'  => $this->user?->name,
            'user email' => $this->user?->email,
        ],
    ],
];
```

Four details are deliberate:

- **`(string) $this->id`** — numeric strings are required by JSON:API and prevent a
  client from conflating an ID with a number.
- **`whenLoaded('emotions')`** — the relation key is only emitted if it was eager-loaded,
  so the resource is honest about what the query actually loaded. This is what makes
  the missing eager loads in *Drift* visible rather than silently expensive.
- **`$this->category?->name` and `$this->tier?->name`** — relations are projected to
  their label, not nested objects, so the client never has to walk
  `record.category.data.attributes.name`.
- **A separate `EmotionResource`** for the nested collection, so the emotion shape is
  defined once: `{id, name, color}`.

**`HttpResponse` trait** (`app/Traits/HttpResponse.php`) provides `success()` and
`error()` for the endpoints that do *not* return a resource — currently only
`AuthController`:

```php
[
    'status'  => 'Request was successfull',
    'message' => $message,
    'data'    => $data,
]
```

`bootstrap/app.php` configures `shouldRenderJsonWhen(fn ($request) => $request->is('api/*')
|| $request->expectsJson())`, so validation failures and unauthenticated responses on
`/api/*` come back as JSON rather than HTML.

The backoffice does not use Resources — it renders Blade
([ADR-0004](0004-blade-backoffice-for-administration.md)).

## Consequences

### Positive

- Models never leak into JSON. `password`, `remember_token` and any future sensitive
  column cannot be exposed by accident, because the resource enumerates keys explicitly.
- The shape is uniform across all four record surfaces (Meadow, Prism, owner list,
  single record), so the SPA has one decoder.
- `whenLoaded` gives an honest signal: a response missing `emotions` is visible to both
  the reader and any debugbar-style tool.
- A single Resource is the one place to change if a field needs renaming — the SPA
  needs a matching change, but the backend has exactly one edit site.

### Negative

- **Not JSON:API.** It borrows the `attributes`/`relationships` idea but is not
  compliant: no `type`, no `links`, no top-level `data` wrapper, and relationships are
  inlined as plain values rather than resource identifiers with an `included` section.
  A client written against a real JSON:API library will not work.
- **The shape is inconsistent with the auth endpoints.** `/api/records` returns a bare
  Resource; `/api/login` returns the `HttpResponse` envelope; `/api/dashboard/stats`
  returns a raw `response()->json()`. Three conventions on the same API.
- **The client cannot filter by what it can only read as a name.** The Meadow and the
  owner list both accept `?category_id=` / `?tier_id=` as filters, but the resource
  flattens both to a label. The client must keep its own ID→name map, or hardcode the
  seed values.
- **`date` is serialized as a full ISO-8601 timestamp** — `"2026-07-14T00:00:00.000000Z"`
  — even though the column is a `date` and the cast is `'date'`. A milestone journal has
  no meaningful time component, so the client slices the string, and the timezone is
  undeclared.
- `attributes` is a reserved word in the JSON:API spec for exactly this purpose, which
  may create confusion about whether this endpoint is spec-compliant.

### Neutral / follow-on

- [ADR-0003](0003-sanctum-bearer-token-authentication.md) — who may call these.
- [ADR-0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md) — the one
  endpoint that bypasses Resources because it returns aggregated chart data.

## Drift

- **`RecordResource` has a key with a trailing space:** `'category '`
  (`app/Http/Resources/RecordResource.php:25`). Every other key in the same array has
  no trailing whitespace, so this is a typo, not a convention. Any client reading
  `attributes.category` gets `undefined`.
- **The Resource exposes `user email` on the public, unauthenticated Meadow feed.**
  `MeadowController` uses the same `RecordResource` as the authenticated endpoints, and
  the resource unconditionally emits the owner's email
  (`RecordResource.php:34`). `GET /api/blooming-meadow` is registered with no
  authentication middleware, so any anonymous visitor can harvest the email address of
  every user who has published a record.
- **Category and tier are flattened to names, and the response carries no id for either.**
  The Meadow and the owner list both accept `?category_id=` / `?tier_id=` as filters, but
  the response contains only the label, so a client that renders a filter UI cannot map
  a selection back to a value. The client has to maintain its own ID→name map, or
  hardcode the seed values.
- **`date` arrives as a full ISO-8601 timestamp** (`"2026-07-14T00:00:00.000000Z"`) for a
  `date` column, and `visibility` arrives as a plain string, because Laravel's serializer
  unwraps `BackedEnum`. Both are unstated contract details that the SPA has to match by
  observation.
- **The `HttpResponse` trait's `success()` always emits the literal string
  `'$message'`** — single quotes in `app/Traits/HttpResponse.php:16` mean the nine
  characters `$message` rather than the variable's value. Every success response from
  `/api/login`, `/api/register` and `/api/logout` therefore has a useless `message`.
  `error()` uses double quotes and is unaffected.
- **`error()` declares an optional parameter before a required one** — `error($data,
  $message = null, $code)`. PHP 8 deprecates this and treats `$message` as implicitly
  required, so the signature is misleading and emits a deprecation notice on every call.
- **`AuthController::register()` returns the full `User` model** inside the envelope's
  `data.user`. `$hidden` on the model saves `password` and `remember_token`, but the
  shape is model-driven rather than resource-driven, so it is one new column away from
  leaking.
