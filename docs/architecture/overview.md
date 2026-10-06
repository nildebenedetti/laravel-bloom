# Architecture overview

How Bloom is put together, and why. For the *why*, see the
[ADRs](../adr/README.md). This document describes the shape of the system as it exists.

## What Bloom is

A journaling and milestone-tracking platform. A user records something that happened to
them, dates it, and classifies it along three axes:

| Axis | Question it answers | Storage |
| --- | --- | --- |
| **Category** | What area of life? | `records.category_id` → `categories` |
| **Tier** | How big was it? | `records.tier_id` → `tiers` |
| **Emotion** | How did it feel? (many at once) | `emotion_record` pivot → `emotions` |

Every record is also **public** or **private**, which is the pivot between the product's
two experiences.

## System context

```
        ┌──────────────────────────────┐
        │   Bloom SPA (React, Vite)    │   separate repository
        │   http://localhost:5173      │
        └──────────────┬───────────────┘
                       │  HTTPS / JSON
                       │  Authorization: Bearer <sanctum token>
                       │  CORS origin: http://localhost:5173
                       ▼
┌──────────────────────────────────────────────────────────────────┐
│  laravel-bloom  (this repository)                                │
│                                                                  │
│   ┌────────────────────┐      ┌──────────────────────────────┐   │
│   │  /api/*            │      │  /  /dashboard  /admin/*     │   │
│   │  JSON for the SPA  │      │  Blade for humans            │   │
│   │  auth:sanctum      │      │  session cookie + IsAdmin    │   │
│   │  Api\ controllers  │      │  Admin\ controllers          │   │
│   └─────────┬──────────┘      └──────────────┬───────────────┘   │
│             │                                │                   │
│             └────────────┬───────────────────┘                   │
│                          ▼                                       │
│              ┌───────────────────────┐                           │
│              │  Eloquent models      │                           │
│              │  Api\Resources        │                           │
│              │  Form Requests        │                           │
│              └───────────┬───────────┘                           │
│                          ▼                                       │
│              ┌───────────────────────┐   ┌──────────────────┐    │
│              │  MySQL 8              │   │  public disk     │    │
│              │  (SQLite in tests)    │   │  storage/app/pub │    │
│              └───────────────────────┘   └──────────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```

**Two front doors, one domain.** The API and the backoffice are separate HTTP surfaces
over the same models. They duplicate some logic, they validate differently, and they have
different authorization models. That duplication is the system's main structural
characteristic and the root of most items in [technical-debt.md](../technical-debt.md).

## The two front doors

|  | API | Backoffice |
| --- | --- | --- |
| **Routes** | `routes/api.php` | `routes/web.php` |
| **Prefix** | `/api` | `/`, `/dashboard`, `/admin`, `/profile` |
| **Response** | JSON via `RecordResource` / raw `response()->json()` | Blade views |
| **Auth** | `auth:sanctum` bearer token | session cookie (`auth` middleware) |
| **CSRF** | not applicable | `@csrf` on every form |
| **Authorization** | inline ownership check ([ADR-0010](../adr/0010-ownership-checks-in-controllers-instead-of-policies.md)) | `IsAdmin` middleware ([ADR-0011](../adr/0011-role-column-and-admin-middleware.md)) |
| **Validation** | Form Requests | mostly none |
| **Who uses it** | the SPA | administrators |
| **Controllers** | `App\Http\Controllers\Api\*` | `App\Http\Controllers\Admin\*` |

## Layers

There is no service layer, no repository pattern, and no DTOs. The app is four layers
deep, and most controllers are one or two layers deep:

```
routes/api.php, routes/web.php
        │
        ▼
app/Http/Controllers/
  ├── Api/        JSON, Sanctum
  ├── Admin/      Blade, session
  ├── Auth/       Breeze (session)
  ├── AuthController   JSON login/register/logout
  └── ProfileController
        │
        ▼
app/Http/
  ├── Requests/   validation (Api\, root, Auth\)
  ├── Resources/  serialization (RecordResource, EmotionResource, UserResource)
  └── Middleware/ IsAdmin
        │
        ▼
app/Models/  ├── app/Enums/RecordVisibility
             └── app/Traits/HttpResponse  (used by AuthController only)
        │
        ▼
MySQL + public disk
```

Notably absent: `app/Services/`, `app/Repositories/`, `app/Actions/`, `app/Jobs/`,
`app/Events/`, `app/Listeners/`, `app/Policies/`, `app/Casts/`. The only custom
abstractions are the `HttpResponse` trait, the `RecordVisibility` enum, and
`IsAdmin` middleware.

## Request lifecycle

### A record read through the API

```
GET /api/records?search=garden&emotions[]=1&order=asc
Authorization: Bearer 1|xxxxxxxx…

 1. Kernel → 'api' middleware group → HandleCors reads config/cors.php
             (origin must be http://localhost:5173 or the preflight fails)
 2. auth:sanctum  →  personal_access_tokens lookup  →  $request->user()
 3. Api\RecordController@index
 4.   $request->user()->records()          ← scoping by owner starts here
 5.   ->when(filled('search'))             → WHERE title LIKE '%garden%'
 6.   ->when(filled('emotions'))           → whereHas → EXISTS subquery
 7.   ->when(filled('category_id'))
 8.   ->when(filled('tier_id'))
 9.   ->with(['category','tier','emotions'])   ← 'user' is MISSING
10.   ->orderBy('date', $sortOrder)
11.   ->paginate(20)                       → 1 count + 1 page + relations
12. RecordResource::collection($records)
13.   → for each record, reads category, tier, emotions AND user
        'user' was not eager-loaded ⇒ 20 extra queries
14. JSON 200
```

Step 13 is the whole N+1 story: the resource reads four relations and the query
eager-loads three.

### A record write through the API

```
POST /api/records   (multipart/form-data)

 1. auth:sanctum
 2. StoreRecordRequest::rules()  →  8 required/nullable rules run before the controller
 3. Api\RecordController@store
 4.   $validated = $request->validated()
 5.   if hasFile('image') → $validated['image_path'] = file->store('records')
 6.   $unset($validated['image']);      ← FATAL. This is a variable holding a
 7.   Record::create([...])                 function-call expression, not `unset()`.
                                           The request never completes.
```

`POST /api/records` currently returns HTTP 500 on every request. See
[technical-debt.md](../technical-debt.md#td-01).

### A backoffice page

```
GET /admin/records
Cookie: laravel_session=…

 1. Kernel → 'web' middleware group → StartSession, VerifyCsrfToken, SubstituteBindings
 2. auth          → session-based, redirects to /login if absent
 3. verified      → NO-OP: User does not implement MustVerifyEmail
 4. IsAdmin       → auth()->user()->isAdmin(); abort(403) otherwise
 5. Admin\RecordController@index
 6.   Record::latest('date')->get()   ← no eager load
 7. view('records.index', …)
 8.   each <x-record-card> reads $record->user?->name
        → 1 query per card
 9. HTML 200
```

## Naming and vocabulary

| Term | Where | Meaning |
| --- | --- | --- |
| **Record** | `records` | a journal entry / milestone. The core aggregate. |
| **Blooming Meadow** | `GET /api/blooming-meadow` | public, anonymous, read-only feed of public records |
| **Prism** | `GET /api/prism` | the authenticated owner's records, filtered by emotion |
| **Stats** | `GET /api/dashboard/stats` | three chart datasets (spider, pie, area) |
| **Backoffice** | `/admin/*` | server-rendered admin surface |
| **Tier** | `tiers` | size of the achievement: small win → epic breakthrough |
| **Category** | `categories` | area of life: Career, Studies, Bonds, … |
| **Emotion** | `emotions` | feeling, many per record, each with a hex colour |

The metaphor is consistent: one record is a **bloom**; its emotions are the **prism**
it refracts through; the public feed is the **meadow** all the blooms are visible in.

## Configuration that matters

| File | What it controls | Current value |
| --- | --- | --- |
| `config/cors.php` | which origins may call `/api/*` | `http://localhost:5173`, **hardcoded** |
| `.env` `DB_CONNECTION` | database engine | `mysql` |
| `.env` `FILESYSTEM_DISK` | image storage disk | `public` |
| `.env` `SESSION_DRIVER` | backoffice sessions | `database` |
| `phpunit.xml` | test database | `sqlite` `:memory:` |
| `bootstrap/app.php` | `withMiddleware()` | **empty** — no aliases, no `statefulApi()` |
| `bootstrap/app.php` | `shouldRenderJsonWhen` | `api/*` or `expectsJson()` |

`bootstrap/app.php` is worth reading in full; it is 22 lines and two of them matter more
than the rest:

```php
->withMiddleware(function (Middleware $middleware): void {
    //  <- empty: no aliases, no statefulApi()
})
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );
})
```

## Where to look next

- [Data model](data-model.md) — tables, columns, relationships, migrations
- [API reference](../api/reference.md) — every endpoint, with curl examples
- [Setup & contribution guide](../guides/setup-and-contribution.md) — running it locally
- [Technical debt](../technical-debt.md) — what is currently broken
- [ADRs](../adr/README.md) — why it is like this
