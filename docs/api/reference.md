# API reference

Every route in `routes/api.php`, with authentication, parameters, response shapes and
working curl examples. All paths are prefixed with `/api`.

- **Base URL (local):** `http://localhost:8000/api`
- **Content type:** `application/json`, except `POST /records` which is
  `multipart/form-data`
- **Authentication:** `Authorization: Bearer <token>` from `POST /api/login` or
  `POST /api/register`
- **CORS:** only `http://localhost:5173` is allowed (hardcoded in `config/cors.php`)

> **Read this first.** Two endpoints are currently broken and are marked ⚠️ below:
> `POST /api/records` fails on every request, and `GET /api/blooming-meadow` leaks the
> email addresses of every publishing user. Details and fixes in
> [technical-debt.md](../technical-debt.md).

## At a glance

| Method | Path | Auth | Purpose | Status |
| --- | --- | --- | --- | --- |
| `POST` | `/register` | — | create an account, return a token | 200 (should be 201) |
| `POST` | `/login` | — | exchange credentials for a token | ok |
| `GET` | `/blooming-meadow` | — | public feed of `public` records | ⚠️ leaks emails |
| `POST` | `/logout` | token | revoke the current token | ok |
| `GET` | `/user` | token | the authenticated user | ok |
| `GET` | `/records` | token | the caller's records, filtered/sorted/paginated | ok, N+1 |
| `POST` | `/records` | token | create a record | ⚠️ **500 on every request** |
| `GET` | `/records/{id}` | token | one record, owner only | ok |
| `PUT`/`PATCH` | `/records/{id}` | token | update a record, owner only | ⚠️ 500 on non-owner; image no-op |
| `DELETE` | `/records/{id}` | token | delete a record, owner only | ⚠️ 500 on non-owner; 500 if it has an image |
| `GET` | `/prism` | token | the caller's records, filtered by emotion | ok, N+1 |
| `GET` | `/dashboard/stats` | token | three chart datasets | ⚠️ MySQL-only |

---

## Authentication

### `POST /api/register`

Public. Creates a user and returns a fresh bearer token.

| Field | Type | Rules |
| --- | --- | --- |
| `name` | string | required, max 255 |
| `email` | string | required, valid email, max 255, unique in `users` |
| `password` | string | required, **must be confirmed**, `Password::defaults()` (min 8) |
| `password_confirmation` | string | required — the client must send it |

```bash
curl -X POST http://localhost:8000/api/register \
  -H 'Content-Type: application/json' \
  -H 'Origin: http://localhost:5173' \
  -d '{
    "name": "Ophelia",
    "email": "ophelia@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**200** (a `201 Created` would be more correct):

```json
{
  "status": "Request was successful",
  "message": "",
  "data": {
    "user": { "id": 3, "name": "Ophelia", "email": "ophelia@example.com", "role": "user" },
    "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

> `message` is always the empty string, because `AuthController` calls `success()` without a
> second argument and `app/Traits/HttpResponse.php:17` interpolates `$message` inside double
> quotes. The token and user are correct.

The `user` object is the model serialized directly, not a Resource. `password` and
`remember_token` are hidden by the model's `$hidden`.

---

### `POST /api/login`

Public. Verifies credentials and mints a new token.

| Field | Type | Rules |
| --- | --- | --- |
| `email` | string | required, valid email |
| `password` | string | required, min 6 |

```bash
curl -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -H 'Origin: http://localhost:5173' \
  -d '{"email": "admin@bloom.org", "password": "safepsw@bloom2026"}'
```

**200** on success, same body shape as `register`.

**401** on bad credentials:

```json
{ "status": "Error has occurred", "message": "credentials do not match", "data": "" }
```

> **No rate limiting.** Unlike the Breeze web login, this endpoint has no throttle, so
> it is an unthrottled credential-stuffing target.

A new token is created on every call. **Tokens expire after 72 hours** — see
[Token lifetime](#token-lifetime) below.

---

### `POST /api/logout`

Requires a token. Deletes **only the current** token; other tokens for the same user
remain valid.

```bash
curl -X POST http://localhost:8000/api/logout \
  -H 'Authorization: Bearer 3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' \
  -H 'Origin: http://localhost:5173'
```

**200**:

```json
{
  "status": "Request was successful",
  "message": "",
  "data": { "message": "You have successfully been logged out. Come back soon!" }
}
```

---

### `GET /api/user`

Requires a token. Returns the raw `User` model — no Resource.

```bash
curl http://localhost:8000/api/user \
  -H 'Authorization: Bearer 3|xxxx' -H 'Origin: http://localhost:5173'
```

```json
{ "id": 3, "name": "Ophelia", "email": "ophelia@example.com", "role": "user" }
```

> **This is the only authenticated endpoint that does not use the `HttpResponse`
> envelope.** It returns the serialized model directly, so a client that unwraps
> `{ status, message, data }` uniformly will read `undefined` here.

---

## Token lifetime

Tokens are **not** permanent. Sanctum applies two independent checks in
`Guard::isValidAccessToken()` (`vendor/laravel/sanctum/src/Guard.php:128`):

```php
$isValid = (! $this->expiration || $accessToken->created_at->gt(now()->subMinutes($this->expiration)))
         && (! $accessToken->expires_at  || ! $accessToken->expires_at->isPast());
```

| Check | Source | Value here | Applies to |
| --- | --- | --- | --- |
| `expiration` | `config/sanctum.php:53` | **`4320` minutes = 72 hours** | every token, **including ones already issued** |
| `expires_at` | per-token column | always `null` | only if `createToken()` receives an expiry |

Because `AuthController` calls `createToken()` without an expiry, the `expires_at` column is
never populated and **only the global `expiration` check applies**. A token is rejected
`created_at > 72h` after its creation, regardless of the column.

**Client impact:** a returning user whose token is older than three days gets `401
{"message":"Unauthenticated."}` on their first authenticated request, including the
`GET /api/user` a SPA uses to restore a session. This is expected behaviour, not an outage.

> **The `sanctum:prune-expired` schedule does not clean up expired tokens.** The command
> deletes rows whose `expires_at` is in the past (`PruneExpired.php:39`), and that column is
> never set, so the daily job in `routes/console.php:11` removes nothing. Expired tokens
> become *invalid* but stay in the table forever. See
> [ADR-0003](../adr/0003-sanctum-bearer-token-authentication.md).

---

## Records

All record endpoints return a `RecordResource`. The shape is JSON:API-*inspired*:

```json
{
  "data": [
    {
      "id": "1",
      "attributes": {
        "title": "Finished my thesis",
        "description": "Three years of work, finally submitted.",
        "date": "2026-07-14T00:00:00.000000Z",
        "image_path": "records/abc123.jpg",
        "image_alt": "A stack of printed manuscripts",
        "category ": "Studies",
        "tier": "epic breakthrough",
        "visibility": "private",
        "emotions": [
          { "id": 1, "name": "Proud", "color": "#4a90d9" }
        ]
      },
      "relationships": {
        "user": { "id": "3", "user name": "Ophelia", "user email": "ophelia@example.com" }
      }
    }
  ],
  "links": { "first": null, "last": null, "prev": null, "next": "…?page=2" },
  "meta": { "current_page": 1, "from": 1, "last_page": 4, "per_page": 20, "to": 20, "total": 68 }
}
```

Five things to know about that shape:

- **`"category "` has a trailing space** in the key. Read `attributes["category "]`,
  not `attributes.category`. This is a typo.
- **`date` is a full ISO-8601 timestamp**, not a date — `"2026-07-14T00:00:00.000000Z"`
  — even though the column is a `date` and the cast is `'date'`. The client must slice
  it. The timezone is UTC and is not declared anywhere in the API.
- **`visibility` is a plain string** (`"public"` / `"private"`). Laravel's serializer
  unwraps `BackedEnum`, so the enum reaches the client as its backing value.
- **`category` and `tier` are names, not ids.** The response contains no id for either,
  so a client that needs to filter must keep its own id→name map.
- **`emotions` is only present if the relation was eager-loaded.** It is always present
  on the record endpoints; it may be absent elsewhere.

### `GET /api/records`

Requires a token. Returns **only the caller's records** — scoped from
`$request->user()->records()`, not from `Record::`, so ownership is correct by
construction.

| Query param | Type | Default | Effect |
| --- | --- | --- | --- |
| `search` | string | — | `WHERE title LIKE '%…%'` |
| `emotions[]` | int[] | — | `whereHas` → record has **any** of these emotions |
| `category_id` | int | — | exact match |
| `tier_id` | int | — | exact match |
| `order` | `asc` \| anything else | `desc` | `ORDER BY date`. Case-insensitive; only `asc` is honoured |
| `page` | int | 1 | Laravel pagination |

**Page size is fixed at 20** and is not configurable.

```bash
curl 'http://localhost:8000/api/records?emotions[]=1&emotions[]=3&order=asc&search=thesis' \
  -H 'Authorization: Bearer 3|xxxx' -H 'Origin: http://localhost:5173'
```

> Eager loads `category`, `tier`, `emotions` — **not `user`**, which the resource reads.
> 20 extra queries per page. See
> [ADR-0017](../adr/0017-phpunit-feature-tests-from-the-breeze-baseline.md).

---

### `POST /api/records` ⚠️ **currently 500 on every request**

Requires a token. `multipart/form-data`.

| Field | Type | Rules |
| --- | --- | --- |
| `title` | string | required, max **255** (column is 200) |
| `description` | string | nullable |
| `date` | date | required |
| `image` | file | nullable, image, `jpg\|jpeg\|png\|webp`, max 2048 KB |
| `image_alt` | string | nullable, max 255 |
| `visibility` | string | required, `public` \| `private` |
| `category_id` | int | required, must exist in `categories` |
| `tier_id` | int | required, must exist in `tiers` |
| `emotions[]` | int[] | nullable, each must exist in `emotions` |
| `emotions` | array | nullable |

`user_id` is set from the token; it cannot be spoofed. Emotions are attached with
`attach()`.

```bash
curl -X POST http://localhost:8000/api/records \
  -H 'Authorization: Bearer 3|xxxx' \
  -H 'Origin: http://localhost:5173' \
  -F 'title=Finished my thesis' \
  -F 'description=Three years of work, finally submitted.' \
  -F 'date=2026-07-14' \
  -F 'visibility=private' \
  -F 'category_id=2' \
  -F 'tier_id=4' \
  -F 'emotions[]=1' \
  -F 'emotions[]=3' \
  -F 'image_alt=A stack of printed manuscripts' \
  -F 'image=@/path/to/photo.jpg'
```

**The current response is 500:**

```
Error: Call to undefined function $unset()
```

`app/Http/Controllers/Api/RecordController.php:73` reads `$unset($validated['image']);`
— a variable holding a function-call expression, not the `unset()` language construct.
The fix is `unset($validated['image']);`. Because the file is uploaded successfully
*before* the crash, **a retrying client orphans the file on disk every time.**

---

### `GET /api/records/{id}`

Requires a token. Owner only.

| Status | Condition |
| --- | --- |
| `200` | you own it |
| `403` | you do not own it |
| `404` | no such record |

```bash
curl http://localhost:8000/api/records/12 \
  -H 'Authorization: Bearer 3|xxxx' -H 'Origin: http://localhost:5173'
```

Eager loads `category`, `tier`, `user`, `emotions` — complete. This is the only record
endpoint with no N+1.

> Note: an administrator cannot read another user's record through the API, even though
> they can through the backoffice.

---

### `PUT|PATCH /api/records/{id}`

Requires a token. Owner only. ⚠️ **Currently two things are wrong here.**

The same `StoreRecordRequest` validates both create and update, and every field is
`required` — so a partial update is **always 422**. To use this endpoint today, send the
full representation:

```bash
curl -X PUT http://localhost:8000/api/records/12 \
  -H 'Authorization: Bearer 3|xxxx' \
  -H 'Origin: http://localhost:5173' \
  -F 'title=Finished my thesis (revised)' \
  -F 'description=Three years of work, finally submitted.' \
  -F 'date=2026-07-14' \
  -F 'visibility=private' \
  -F 'category_id=2' \
  -F 'tier_id=4' \
  -F 'emotions[]=1'
```

Two defects:

1. **Non-owners get 500, not 403.** Line 98 calls `error(...)` instead of
   `$this->error(...)` — no such global function exists.
2. **Image upload is silently ignored.** Line 105 checks
   `$request->hasFile('iamge')`. A request that also contains an image would hit
   `Storage::disk('records')` at line 106, and no disk named `records` is configured —
   so it throws instead.

Emotions use `sync()`, so the submitted set fully replaces the previous set.

---

### `DELETE /api/records/{id}`

Requires a token. Owner only. Returns **204 No Content** on success.

```bash
curl -X DELETE http://localhost:8000/api/records/12 \
  -H 'Authorization: Bearer 3|xxxx' -H 'Origin: http://localhost:5173'
```

| Status | Condition |
| --- | --- |
| `204` | deleted |
| `500` | you do not own it (bare `error()` call) |
| `500` | the record **has** an image — `Storage::disk('records')` does not exist |

A record with no `image_path` deletes correctly, which is why this is not obviously
broken. The database row is removed; the file on disk is left behind in every case.

---

## Public feed

### `GET /api/blooming-meadow` ⚠️ **leaks user email addresses**

**No authentication.** Returns every record with `visibility = 'public'`, across all
users.

| Query param | Type | Effect |
| --- | --- | --- |
| `category_id` | int | exact match |
| `emotions[]` | int[] | `whereHas` → record has any of these emotions |

**Page size is fixed at 15** — different from the 20 used elsewhere. Ordered by
`created_at desc` (posting order), not `date`.

```bash
curl 'http://localhost:8000/api/blooming-meadow?category_id=1&emotions[]=2' \
  -H 'Origin: http://localhost:5173'
```

```json
{
  "data": [
    {
      "id": "8",
      "attributes": { "title": "Ran my first marathon", "…": "…" },
      "relationships": {
        "user": {
          "id": "2",
          "user name": "Ophelia",
          "user email": "ophelia@example.com"
        }
      }
    }
  ]
}
```

> **This is a privacy defect, not a design choice.** The endpoint is anonymous, and the
> shared `RecordResource` unconditionally emits the owner's email
> (`app/Http/Resources/RecordResource.php:34`). Anyone can harvest the email address of
> every user who has ever published a record.
>
> The fix is to give the public surface its own resource without the email field, or to
> add a `when()` conditional. See
> [technical-debt.md](../technical-debt.md#td-04).

Also: this controller has **no eager loading at all**, so each of the 15 records costs
four extra queries — 61 queries for one page of a public feed.

---

## Analytics

### `GET /api/prism`

Requires a token. The caller's records, filtered and sorted by emotion — the
"emotional exploration" view.

| Query param | Type | Default | Effect |
| --- | --- | --- | --- |
| `emotions[]` | int[] | — | `whereHas` → record has any of these emotions |
| `order` | `asc` \| anything else | `desc` | `ORDER BY date` |

**Page size 20.** Unlike `/api/records` there is no `search`, `category_id` or `tier_id`
filter — the Prism is emotion-only by design.

```bash
curl 'http://localhost:8000/api/prism?emotions[]=5&order=asc' \
  -H 'Authorization: Bearer 3|xxxx' -H 'Origin: http://localhost:5173'
```

> Calls `->load('emotions')` on the **paginator** rather than the builder. It works
> only because `AbstractPaginator` proxies `__call` to its items. `category`, `tier` and
> `user` are never loaded, so this is the worst N+1 of the three list endpoints.

---

### `GET /api/dashboard/stats` ⚠️ **MySQL-only**

Requires a token. Returns three pre-shaped chart datasets in one response.

| Query param | Values | Default |
| --- | --- | --- |
| `time_range` | `all_time` \| `last_month` \| `last_six_months` | `all_time` |

An unrecognised value is silently treated as `all_time` — there is no `else` and no
validation.

```bash
curl 'http://localhost:8000/api/dashboard/stats?time_range=last_six_months' \
  -H 'Authorization: Bearer 3|xxxx' -H 'Origin: http://localhost:5173'
```

```json
{
  "time_range": "last_six_months",
  "charts": {
    "spider": [
      { "emotion": "Proud",    "count": 12 },
      { "emotion": "Grateful", "count": 9 }
    ],
    "pie": [
      { "category": "Career", "count": 18 },
      { "category": "Studies", "count": 11 }
    ],
    "area": [
      { "month": "2026-04", "tier_1": 2, "tier_3": 5 },
      { "month": "2026-05", "tier_1": 1, "tier_2": 4, "tier_3": 7 }
    ]
  }
}
```

| Key | Chart | SQL | PHP |
| --- | --- | --- | --- |
| `spider` | radar, one axis per emotion | `emotions` ⋈ `emotion_record` ⋈ `records`, `GROUP BY emotions.id, emotions.name` | — |
| `pie` | share by category | `records` ⋈ `categories`, `GROUP BY category` | — |
| `area` | records per month, stacked by tier | `records` ⋈ `tiers`, `DATE_FORMAT(date,'%Y-%m')`, `GROUP BY month, tier` | reshaped into wide `{month, tier_<id>}` objects, `array_values()` |

Notes:

- **`tier_<id>` keys are opaque.** There are no tier names in the response, so a client
  cannot label the stacked areas without its own id→name map.
- **`DATE_FORMAT` is MySQL-only.** The test suite runs on SQLite, where this endpoint
  throws `no such function: DATE_FORMAT`. There is no test for it.
- **The three queries are not in a transaction**, so the datasets can be mutually
  inconsistent if a record is created mid-request.
- **Empty months are omitted entirely.** A Recharts stacked area with a gap in the
  `data` array renders a discontinuity, not a zero.

---

## Validation errors

Any `422` from `/api/*` returns Laravel's standard shape:

```json
{
  "message": "The title field is required. (and 3 more errors)",
  "errors": {
    "title": ["The title field is required."],
    "date":  ["The date field is required."],
    "category_id": ["The category id field is required."]
  }
}
```

This works because of `bootstrap/app.php`:

```php
$exceptions->shouldRenderJsonWhen(
    fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
);
```

## Unauthenticated responses

A request to a protected route without a token returns **401** with
`{"message": "Unauthenticated."}` — the `auth:sanctum` guard, not a custom response. The
`HttpResponse` trait is not involved.

## Conventions summary

| Concern | Convention | Note |
| --- | --- | --- |
| Auth header | `Authorization: Bearer <token>` | Sanctum personal access token |
| IDs in `RecordResource` | stringified `(string)` | JSON:API convention |
| IDs in `EmotionResource` | native int | **inconsistent with `RecordResource`** |
| Enums | serialized as the backing string | Laravel unwraps `BackedEnum` automatically |
| Dates | full ISO-8601 timestamp | the column is a `date`; the client must slice it |
| Pagination | Laravel `ResourceCollection` | `data` + `links` + `meta` |
| Page sizes | 20 (records, prism), 15 (meadow) | not configurable |
| Errors from controllers | `HttpResponse::error()` → `{status, message, data}` | two call sites are broken |
| Errors from the framework | Laravel's standard `{message, errors}` | |
| Create responses | 200, not 201 | |
