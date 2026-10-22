# Bloom — Backend

Bloom is a journaling and milestone-tracking platform. This repository is the **Laravel
backend**: the JSON API the React SPA consumes, and a server-rendered Blade backoffice
for administrators. The SPA itself is a **separate repository** and is not vendored here.

The premise is simple: accomplishments are not rare. They are small, scattered, and rarely
written down. Bloom makes them visible — and this repository is where they are stored,
classified, filtered and counted.

The product has two faces, and this repository serves both:

- **The Blooming Meadow** — a public, anonymous feed of the milestones people chose to
  share. Served here as a read-only JSON endpoint.
- **The private suite** — write, organise, revisit and measure your own milestones: a
  journal, an image-forward *Prism*, and an analytics dashboard.

The domain metaphor drives the naming: a record is a **bloom**, its emotions are the
**prism** it refracts through, and the shared feed is the **meadow**.

Each record is classified along three independent axes, plus a visibility flag:

| Axis | Meaning | Examples |
| --- | --- | --- |
| **Category** | area of life | Career, Studies, Sports, Cooking, Travel, … |
| **Tier** | magnitude of the achievement | small win → solid step → major milestone → epic breakthrough |
| **Emotions** | how it felt | Proud, Relieved, Excited, Determined, Grateful, … |
| **Visibility** | public or private | the hinge between the Meadow and your journal |

Visibility is decided per record: a **public** record is what readers of the Meadow see, a
**private** one stays in its author's journal.

## Stack

| | |
| --- | --- |
| Framework | Laravel 13 · PHP 8.3 |
| API auth | Sanctum 4 (bearer tokens) |
| Backoffice auth | Breeze 2.4 (session cookies) |
| Database | MySQL 8 (SQLite in tests) |
| Backoffice UI | Blade + Bootstrap 5 + Vite |
| Tooling | PHPUnit 12 · Pint |

The backend is **authoritative**: validation, aggregation and authorisation live here.
The SPA renders what this API decides — it never re-derives a count, a filter or a
permission.

---

## Architecture

### At a glance

```txt
        ┌──────────────────────────────┐
        │   Bloom SPA (React, Vite)    │   separate repository
        │   http://localhost:5173      │
        └──────────────┬───────────────┘
                       │  HTTP / JSON
                       │  Authorization: Bearer <Sanctum token>
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

Two front doors, one domain. The API and the backoffice are separate HTTP surfaces over
the same models: they share the database, the models and the serialization contract, and
each keeps the authorization strategy and the validation approach that suits its
consumer.

### The two front doors

| | API | Backoffice |
| --- | --- | --- |
| **Routes** | `routes/api.php` | `routes/web.php` |
| **Prefix** | `/api` | `/`, `/dashboard`, `/admin`, `/profile` |
| **Response** | JSON via `RecordResource` | Blade views |
| **Auth** | `auth:sanctum` bearer token | session cookie |
| **Authorization** | ownership check per action | `IsAdmin` middleware |
| **Validation** | Form Requests | inline `$request->validate()` |
| **Consumers** | the SPA | administrators |

There is no service layer, no repository pattern and no DTOs. The app is four layers
deep, and most controllers are one or two layers deep:

```txt
routes/api.php, routes/web.php
        │
        ▼
app/Http/Controllers/
  ├── Api/        JSON, Sanctum
  ├── Admin/      Blade, session
  ├── Auth/       Breeze (session)
  ├── AuthController    JSON login/register/logout
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

The only custom abstractions are the `HttpResponse` trait, the `RecordVisibility` enum
and the `IsAdmin` middleware. Everything else is framework machinery.

### The request pipeline

Every call the SPA makes lands in the same nine steps. This is the server half of the
`fetchData` wrapper documented in the SPA's README: the client owns the transport, the
server owns the contract.

```txt
GET /api/records?search=garden&emotions[]=1&order=asc
Authorization: Bearer 1|xxxxxxxx…

 1. 'api' middleware group → HandleCors reads config/cors.php
    (the Origin must be http://localhost:5173, or the preflight fails)
 2. auth:sanctum → personal_access_tokens lookup → $request->user()
 3. Api\RecordController@index
 4.   $request->user()->records()          ← ownership scoping starts here
 5.   ->when(filled('search'))             → WHERE title LIKE '%garden%'
 6.   ->when(filled('emotions'))           → whereHas → EXISTS subquery
 7.   ->with(['category','tier','emotions','user'])
 8.   ->orderBy('date', $sortOrder) → paginate(20)
 9. RecordResource::collection($records) → { data, links, meta }
```

Step 4 is the authorisation: the query starts from the *relationship*
(`$request->user()->records()`), not from `Record::`, so a caller's records are scoped at
the query level and there is no way to read someone else's by accident. Steps 5 and 6
are the filters; step 7 is the eager loading that keeps the response from turning into
an N+1.

### The response contract

Three success shapes and one error shape. A client has to know which one it is reading,
because they are not the same:

| Shape | Where | Body |
| --- | --- | --- |
| **Envelope** | `AuthController` — login, register, logout | `{ status, message, data }` |
| **Resource** | every record endpoint | `{ data, links, meta }` for a collection, `{ data }` for a single record |
| **Bare** | `UserResource` — `/api/user`, `/api/user/bio` | the object itself, no wrapper |

The **envelope** comes from `app/Traits/HttpResponse.php` and is the shape the SPA's
`unwrapEnvelope` looks for:

```json
{
  "status": "Request was successful",
  "message": "",
  "data": { "user": { "id": 7, "name": "Admin", "email": "admin@bloom.org" }, "token": "67|…" }
}
```

The **resource** shape is Laravel's `JsonResource` with the default `data` wrapper, plus
`links` and `meta` when the resource is a collection — that is where the SPA finds the
current page and the `next` link. A single record is the same object, wrapped once:

```json
{
  "data": {
    "id": "102",
    "attributes": {
      "title": "Readme smoke test",
      "description": "Created to verify the response contract.",
      "date": "2026-10-22",
      "image_path": "records/QEoJWZXGMyi0AGIKd0caARlc6v6Y4rLw6WbIfrXz.png",
      "image_alt": "A test image",
      "category": "Career",
      "tier": { "id": 2, "name": "solid step" },
      "visibility": "public",
      "emotions": [{ "id": 1, "name": "Proud", "color": "#ffba42" }]
    },
    "relationships": {
      "user": { "id": 8, "user_name": "Ophelia", "email": "offHell@live.com" }
    }
  }
}
```

`RecordResource` is JSON:API-*inspired*: attributes are separated from relationships, so
a client renders a record from `attributes` and resolves ownership from `relationships`.
Two details are worth knowing: `category` is a **name** and `tier` is an **object**
(`{ id, name }`), and `date` is a plain `Y-m-d` string, not a timestamp. The owner's
`email` is emitted only for the owner or an administrator — the Meadow is anonymous, so
it never leaks one.

The **bare** shape is `UserResource`, which opts out of the `data` wrapper with
`public static $wrap;`: `GET /api/user` has always returned the user object itself, and
the SPA reads it unwrapped.

```json
{
  "id": 7,
  "name": "Admin",
  "email": "admin@bloom.org",
  "role": "admin",
  "bio": "System Administrator, Master of Puppets and cat lover.",
  "email_verified_at": null,
  "created_at": "2026-10-01T15:56:36.000000Z",
  "updated_at": "2026-10-01T15:56:36.000000Z"
}
```

**Errors** have a fourth shape, and it comes from the framework rather than from a
Resource:

| Status | Raised by | Body |
| --- | --- | --- |
| `401` | the `auth:sanctum` guard | `{ "message": "Unauthenticated." }` |
| `403` | the controller's ownership check | `{ status, message, data }` |
| `422` | a Form Request | `{ message, errors: { field: [...] } }` |

`bootstrap/app.php` is what makes the 422 arrive as JSON instead of an HTML page:

```php
$exceptions->shouldRenderJsonWhen(
    fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
);
```

So a `POST /api/records` with a missing title comes back as:

```json
{
  "message": "The title field is required. (and 5 more errors)",
  "errors": {
    "title": ["The title field is required."],
    "date": ["The date field is required."],
    "category_id": ["The category id field is required."]
  }
}
```

…and the SPA's form shows one message per field. The two success shapes are told apart
by the presence of `status`: the envelope has one, the resource collection does not.

### The client that consumes it

The whole client layer of the SPA is a single module, `src/utils/api.js`, and it is the
mirror image of the contract above:

- **`fetchData(endpoint, options)`** — the only `fetch` wrapper. It resolves the base URL
  from `VITE_API_URL`, attaches the bearer token, serialises an `options.params` object
  into a query string (an array becomes Laravel's `key[]`), sets the JSON headers when
  a body is present, and unwraps the `{ status, message, data }` envelope so callers work
  with the payload directly.
- **`ENDPOINTS`** — the one map of public and private routes, so no page hardcodes a
  path. The API side of that map is the endpoint table below.
- **`mediaUrl(path)`** — builds the absolute URL of a stored image, from the API's
  *origin* plus `/storage`. Images are not served under `/api`, so this helper is the
  reason the image path in this README is relative to the disk.

A `401` on an authenticated request clears the token and fires an `auth:unauthorized`
event, so an expired session is cleaned up in one place; `login` and `register` opt out
of that bookkeeping with `anonymous: true`, because a mistyped password is a failed
attempt, not an expired session.

Because the envelope is unwrapped *last*, an error keeps its whole body — which is how
a form gets the per-field `errors` of a 422.

### The endpoints

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| `POST` | `/api/register` | — | create an account, return a token |
| `POST` | `/api/login` | — | exchange credentials for a token |
| `POST` | `/api/logout` | token | revoke the current token |
| `GET` | `/api/user` | token | the authenticated user, including `bio` |
| `PUT` | `/api/user/bio` | token | create or replace the caller's bio |
| `GET` | `/api/blooming-meadow` | — | public feed of shared records |
| `GET` | `/api/records` | token | caller's records, filtered and paginated |
| `POST` | `/api/records` | token | create a record |
| `GET` | `/api/records/{id}` | token | one record, owner only |
| `PUT` `PATCH` | `/api/records/{id}` | token | update a record, owner only |
| `DELETE` | `/api/records/{id}` | token | delete a record, owner only |
| `GET` | `/api/prism` | token | caller's records, filtered by emotion |
| `GET` | `/api/dashboard/stats` | token | three chart datasets |

`/api/records` is a `Route::apiResource`, so the five CRUD routes are generated from one
declaration. Everything else is a custom endpoint, and those are where the product's own
vocabulary lives.

### The custom endpoints

**`GET /api/blooming-meadow`** — the public feed. The only endpoint with no
authentication: it selects `visibility = public` across every user, ordered by
`created_at` (posting order) and paginated by 15. It accepts `category_id` and
`emotions[]`. Because it is anonymous, the `RecordResource` it renders omits the
owner's email.

**`GET /api/prism`** — the image gallery. The caller's own records narrowed to the ones
that carry a picture (`whereNotNull('image_path')`), filtered by `emotions[]` and sorted
by `date`. Emotion-only by design: no search, no category, no tier. Page size 20.

**`GET /api/dashboard/stats`** — the analytics. One request, three pre-shaped datasets,
so the client never aggregates anything:

| Key | Chart | How it is built |
| --- | --- | --- |
| `spider` | radar, one axis per emotion | `emotions ⋈ emotion_record ⋈ records`, `GROUP BY emotions.id, emotions.name` |
| `pie` | share per category | `records ⋈ categories`, `GROUP BY category` |
| `area` | volume per month, stacked by tier | `records ⋈ tiers`, `DATE_FORMAT(date,'%Y-%m')`, `GROUP BY month, tier` |

The `area` rows are reshaped in PHP from `{month, tier, count}` into `{month, tier_1: n,
…}` — the tier keys are tier **ids**, so the client maps them to names with its own
id→name table. `time_range` accepts `all_time`, `last_month` or `last_six_months`; an
unrecognised value is silently treated as `all_time`. `DATE_FORMAT` is MySQL-only, so
this one endpoint does not run on SQLite.

**`PUT /api/user/bio`** — the profile write. `user_profiles` is a `hasOne`, so storing
and updating a bio are the same operation: `updateOrCreate` on the caller's own profile.
No user id travels in the URL or the body, so there is nobody else's bio to authorise
against.

### Filtering

Every list endpoint takes optional parameters and applies them with the same
three-layer pattern:

```php
->when($request->filled('emotions'), function ($query) use ($request) {
    $query->whereHas('emotions', function ($q) use ($request) {
        $q->whereIn('emotions.id', (array) $request->emotions);
    });
})
```

1. `when()` — the parameter is present, so add the clause;
2. `whereHas()` — open an `EXISTS` subquery on the relation;
3. `whereIn()` — match the ids inside it.

The result is "the record has **any** of these emotions", expressed in SQL without
pulling the pivot table into PHP.

| Endpoint | Parameters | Page size |
| --- | --- | --- |
| `/api/records` | `search`, `emotions[]`, `category_id[]`, `tier_id[]`, `order`, `page` | 20 |
| `/api/prism` | `emotions[]`, `order`, `page` | 20 |
| `/api/blooming-meadow` | `category_id`, `emotions[]`, `page` | 15 |
| `/api/dashboard/stats` | `time_range` | — |

`search` is a `LIKE` on the title. `order` is `asc` or anything else, which means
`desc`; only `asc` is honoured, and the comparison is case-insensitive. `category_id`
and `tier_id` accept several ids at once.

`RecordResource` dereferences four relations — `category`, `tier`, `user` and `emotions`
— so **any query that renders records must eager-load all four**:

```php
->with(['category', 'tier', 'user', 'emotions'])
```

Forgetting `user` alone is enough to bring back an N+1: 20 extra queries per page.

### Images

An uploaded image is stored on the `public` disk under `records/`, and only its **path**
is written to the database:

```txt
records/QEoJWZXGMyi0AGIKd0caARlc6v6Y4rLw6WbIfrXz.png
```

That path is relative to the disk, not to the API. Images are **not** served under
`/api`: they live on the public disk (`storage/app/public`, symlinked to
`public/storage`) and are served from the site root, so the URL is
`<origin>/storage/records/…` while the API lives at `<origin>/api`. Concatenating the
two produces a URL that 404s — silently. The SPA therefore builds media URLs with a
dedicated helper, never by hand.

`php artisan storage:link` creates the symlink; without it every image 404s. On update
the previous file is deleted from disk before the new one is written; on delete the
file goes with the row.

### The data model

Eight application tables — one of them a pivot — alongside the framework's supporting
tables.

```txt
        ┌──────────────┐        ┌──────────────────┐
        │    users     │ 1:1    │  user_profiles   │
        │──────────────│───────▶│──────────────────│
        │ id  name     │ hasOne │ id  user_id (uniq)│
        │ email (uniq) │        │ bio (null)       │
        │ role         │        └──────────────────┘
        │   'admin'    │              ▲
        │   | 'user'   │              │ belongsTo
        │ password     │              │
        └──┬───────┬──┘              │
           │       │                 │
     1:N  │       │ N:M             │
   hasMany         │                │
        ┌──────────▼─────────────────▼──────────────────────────┐
        │                      records                            │
        │─────────────────────────────────────────────────────────│
        │ id                                          core        │
        │ title (200)   description (text)   date (date)          │
        │ image_path (null)   image_alt (255, null)              │
        │ visibility  enum('public','private') def 'private'      │
        │ user_id  (FK, nullable)   category_id (FK, NOT NULL)    │
        │ tier_id   (FK, NOT NULL)   created_at / updated_at      │
        └───┬───────────────────────────────┬─────────────────────┘
       N:1  │                               │  N:1
    ┌───────▼────────┐              ┌───────▼─────────┐
    │   categories   │              │      tiers      │
    │ id  name (80)  │              │ id  name (80)   │
    │ description    │              │ description     │
    │ (255, NOT NULL)│             │ (255, nullable) │
    └────────────────┘              └─────────────────┘
            12                            4

    ┌──────────────────┐         ┌────────────────────┐
    │     emotions     │  N:M    │  emotion_record    │
    │──────────────────│◀───────▶│────────────────────│
    │ id  name (70)    │ pivot   │ id                 │
    │ color (hex)      │         │ emotion_id (FK)    │
    │ 7 rows           │         │ record_id  (FK)    │
    └──────────────────┘         │ cascadeOnDelete   │
                                 └────────────────────┘

    ┌────────────────────────────────┐
    │   personal_access_tokens       │  Sanctum, morph → users
    │   token (64, unique)           │
    │   abilities  last_used_at      │
    └────────────────────────────────┘
```

The three taxonomies — `categories`, `tiers`, `emotions` — keep `records` normalised: a
record references a category and a tier by foreign key and carries many emotions through
an explicit pivot. `emotion_record` cascades on delete from both sides, so an emotion or a
record removal cleans up its associations in the same statement. `RecordVisibility` models
the public feed as a first-class, typed value rather than a loose string.

Framework tables: `sessions` (`SESSION_DRIVER=database`), `cache`, `jobs`, and
`password_reset_tokens` (Breeze).

### Relationships

```php
// Record — the core aggregate
belongsTo(Category::class)     // categories
belongsTo(Tier::class)         // tiers
belongsTo(User::class)         // users
belongsToMany(Emotion::class)  // → emotion_record
casts: ['visibility' => RecordVisibility::class, 'date' => 'date']

// User
hasOne(UserProfile::class)     // user_profiles
hasMany(Record::class)         // records
isAdmin(): bool => $this->role === 'admin'

// Category, Tier    hasMany(Record::class)
// Emotion            belongsToMany(Record::class)
// UserProfile        belongsTo(User::class)
```

Ownership is expressed through the relation rather than an id comparison, so
`$request->user()->records()` scopes every record query to the authenticated user at the
query level.

### The backoffice

The SPA is not the only client. Administrators work in a server-rendered Blade surface
under `/admin`, one CRUD screen per table — records, users, categories, tiers and
emotions — reached from a Breeze session login at `/login`.

The `/admin` group applies three middleware: `auth` (a session cookie), `verified`, and
`IsAdmin`, which calls `isAdmin()` on the user and aborts with a 403 for anyone else.
The `role` column (`admin` / `user`) is the only thing that separates the two. (`verified`
is a no-op in practice: `User` does not implement `MustVerifyEmail`.)

The backoffice writes through its own controllers, with inline `$request->validate()`
calls rather than Form Requests, and it saves images on the same `public` disk. It shares
the models and the tables with the API, so a record created in the backoffice is
immediately visible in the Meadow.

### Directory layout

```txt
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/         Record, Meadow, Prism, Dashboard, User  (JSON)
│   │   ├── Admin/       User, Record, Category, Tier, Emotion   (Blade)
│   │   ├── Auth/        9 Breeze controllers (session auth)
│   │   ├── AuthController   JSON login / register / logout
│   │   └── ProfileController
│   ├── Requests/        9 Form Requests (Api\, root, Auth\)
│   ├── Resources/       RecordResource, EmotionResource, UserResource
│   └── Middleware/      IsAdmin
├── Models/              6 Eloquent models
├── Enums/               RecordVisibility
└── Traits/              HttpResponse

routes/                  api.php · web.php · auth.php · console.php
database/                16 migrations · DatabaseSeeder + 5 seeders · 2 factories
resources/views/         43 Blade views
resources/css|js/        the backoffice's assets, compiled by Vite
tests/                   9 files · 25 Breeze test methods
docs/                    architecture, API reference, ADRs, guides
```

---

## Getting started

### Requirements

| Tool | Version | Check |
| --- | --- | --- |
| PHP | ^8.3 | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL | 8.x | `mysql --version` |
| Node | 20+ | `node -v` |
| pnpm | 9+ | `pnpm -v` |

### Setup

```sh
git clone <repo-url> laravel-bloom
cd laravel-bloom

composer install
cp .env.example .env
php artisan key:generate
```

`composer install` runs `php artisan package:discover` automatically.

### The database

The app runs on **MySQL**; `.env.example` ships SQLite. Set the engine explicitly:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel-bloom
DB_USERNAME=root
DB_PASSWORD=
```

```sh
php artisan migrate
php artisan db:seed          # reset + reseed: php artisan migrate:fresh --seed
```

The seeder is not idempotent — running it twice violates the unique constraint on
`users.email`. Use `migrate:fresh --seed` instead.

Seeded accounts:

| Role | Email | Password |
| --- | --- | --- |
| `admin` | `admin@bloom.org` | `safepsw@bloom2026` |
| `user` | `offHell@live.com` | `password123` |

Seeded taxonomy: 12 categories, 4 tiers ordered by significance, and 7 emotions with
generated hex colours.

> The seeder creates two accounts with published passwords. Change or remove them before
> this database goes anywhere near a shared environment.

### Frontend assets

The backoffice's CSS and JS are compiled by Vite into `public/build`:

```sh
pnpm install
pnpm run build        # → public/build
# or, for hot reload:
pnpm run dev
```

`/public/build` and `/public/hot` are gitignored, so the assets are compiled per
environment.

> If `pnpm run dev` has been used, delete `public/hot` before serving the compiled
> build — while that file exists, `Vite::asset()` emits dev-server URLs and every
> asset 404s.

### Run the server

```sh
php artisan serve          # http://localhost:8000
```

Log in at `http://localhost:8000/login` and open `/admin/records`.

### Scripts

| Command | What it does |
| --- | --- |
| `composer install` | install the PHP dependencies |
| `php artisan serve` | run the app on `:8000` |
| `php artisan migrate` | run the migrations |
| `php artisan db:seed` | seed the taxonomy and the accounts |
| `pnpm run dev` | Vite dev server for the backoffice's styles |
| `pnpm run build` | compile the backoffice's assets into `public/build` |
| `composer test` | `config:clear` then `artisan test` |
| `vendor/bin/pint` | format the PHP code |

### Working against the SPA

The SPA is a **separate repository** and it must be running on `http://localhost:5173` —
the only origin `config/cors.php` allows. Run the two processes side by side:

```sh
# in this repository
php artisan serve            # http://localhost:8000

# in the SPA repository
pnpm dev                     # http://localhost:5173
```

The SPA reads its base URL from `VITE_API_URL=http://localhost:8000/api`, and it
authenticates with a Sanctum bearer token obtained from `POST /api/login`.

### Tests

```sh
composer test                  # config:clear && artisan test
php artisan test --filter=AuthenticationTest

vendor/bin/pint                # format
vendor/bin/pint --test         # check only
```

The suite runs against SQLite `:memory:` with array cache, session and queue, keeping
runs fast and self-contained. The 25 tests inherited from the Breeze baseline cover the
web auth and profile flows; the API itself has no feature test yet.
