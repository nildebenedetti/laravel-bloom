# Bloom

Journaling and milestone tracking. A record is a dated event classified along three axes:
**category** (area of life), **tier** (magnitude), and **emotions** (how it felt). Every
record carries a `public` / `private` visibility flag, which is the pivot between the
product's two surfaces.

Terminology follows the domain metaphor: a record is a **bloom**, its emotions are the
**prism** it refracts through, and the public feed is the **meadow**.

| | |
| --- | --- |
| Framework | Laravel 13 · PHP 8.3 |
| Auth | Sanctum 4 (API, bearer tokens) · Breeze 2.4 (web, session) |
| Database | MySQL 8 (SQLite in tests) |
| Frontend | Blade + Bootstrap 5 + Vite |
| Tooling | PHPUnit 12 · Pint |

---

## Architecture

The React SPA is a **separate repository** and is not vendored here. This repository owns
the API and the server-rendered backoffice. The two communicate exclusively over HTTP/JSON,
which keeps presentation and domain concerns independently deployable.

```
        ┌──────────────────────────────┐
        │   Bloom SPA (React, Vite)    │   separate repository
        │   http://localhost:5173      │
        └──────────────┬───────────────┘
                       │  HTTPS / JSON
                       │  Authorization: Bearer <Sanctum token>
                       │  CORS origin: http://localhost:5173
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  laravel-bloom                                                │
│                                                              │
│  ┌────────────────────┐   ┌───────────────────────────────┐  │
│  │  /api/*            │   │  /  /dashboard  /admin/*      │  │
│  │  JSON for the SPA  │   │  Blade backoffice             │  │
│  │  auth:sanctum      │   │  session cookie + IsAdmin     │  │
│  │  Api\* controllers │   │  Admin\* controllers          │  │
│  └─────────┬──────────┘   └──────────────┬────────────────┘  │
│            └────────────┬─────────────────┘                   │
│                         ▼                                     │
│              ┌───────────────────────┐                         │
│              │  Eloquent models      │                         │
│              │  Api\Resources        │                         │
│              │  Form Requests        │                         │
│              └───────────┬───────────┘                         │
│                          ▼                                     │
│              ┌────────────────────┐  ┌─────────────────────┐  │
│              │  MySQL 8            │  │  `public` disk      │  │
│              │  (SQLite in tests)  │  │  storage/app/public │  │
│              └────────────────────┘  └─────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

Two HTTP surfaces over one domain layer. They share the same models, database and
serialization contract, while each keeps an authorization strategy and validation approach
suited to its consumers.

| | API | Backoffice |
| --- | --- | --- |
| Routes | `routes/api.php` | `routes/web.php` |
| Prefix | `/api` | `/`, `/dashboard`, `/admin`, `/profile` |
| Response | JSON via `RecordResource` | Blade views |
| Auth | `auth:sanctum` bearer token | session cookie |
| Authorization | ownership scoped to the authenticated user | `IsAdmin` middleware |
| Validation | Form Requests | controller-level |
| Consumers | the SPA | administrators |

The domain layer is deliberately thin: controllers query Eloquent models directly, with
input validation in Form Requests and output shaping in API Resources. Validation and
serialization are each defined in exactly one place, so the API contract and the
authorization rules are readable end to end. The `RecordVisibility` enum, the
`HttpResponse` trait and the `IsAdmin` middleware are the only cross-cutting abstractions.

---

## Requirements

PHP `^8.3` · Composer 2.x · MySQL 8.x · Node 20+ · pnpm 9+

## Installation

```sh
git clone <repo-url> laravel-bloom
cd laravel-bloom

composer install
cp .env.example .env
php artisan key:generate
```

`/public/build` and `/public/hot` are gitignored, so backoffice assets are compiled per
environment.

## Configuration

The application runs on MySQL. `.env.example` ships SQLite; set the engine explicitly:

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
php artisan db:seed          # reset + reseed: php artisan db:fresh --seed
```

Seeded accounts:

| Role | Email | Password |
| --- | --- | --- |
| `admin` | `admin@bloom.org` | `safepsw@bloom2026` |
| `user` | `offHell@live.com` | `password123` |

Seeded taxonomy: 12 categories, 4 tiers ordered by significance, and 7 emotions with
generated hex colours.

API requests from the SPA must originate from `http://localhost:5173`, the origin
configured in `config/cors.php`.

## Running

```sh
pnpm install
pnpm run build        # or: pnpm run dev
php artisan serve     # http://localhost:8000
```

Log in at `http://localhost:8000/login`, then open `/admin/records`. The React SPA is
started from its own repository on `:5173`.

> If `pnpm run dev` has been used, delete `public/hot` before serving the compiled build —
> while that file exists, `Vite::asset()` emits dev-server URLs.

---

## Data model

Seven application tables plus two join tables, alongside the framework's supporting tables.

```
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
        │ title (200)   description (text)   date (date)  aggregate│
        │ image_path (null)   image_alt (255, null)              │
        │ visibility  enum('public','private') def 'private'      │
        │ user_id  (FK, nullable)   category_id (FK, nullable)    │
        │ tier_id   (FK, nullable)   created_at / updated_at      │
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

---

## API

Base URL `http://localhost:8000/api`. All requests from the SPA carry the
`Origin: http://localhost:5173` header.

Authenticate once and reuse the bearer token for subsequent requests.

```sh
curl -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -H 'Origin: http://localhost:5173' \
  -d '{"email":"admin@bloom.org","password":"safepsw@bloom2026"}'
```

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| `POST` | `/api/register` | — | create an account, return a token |
| `POST` | `/api/login` | — | exchange credentials for a token |
| `POST` | `/api/logout` | token | revoke the current token |
| `GET` | `/api/user` | token | the authenticated user |
| `GET` | `/api/blooming-meadow` | — | public feed of shared records |
| `GET` | `/api/records` | token | caller's records, filtered and paginated |
| `POST` | `/api/records` | token | create a record |
| `GET` | `/api/records/{id}` | token | one record, owner only |
| `PUT` `PATCH` | `/api/records/{id}` | token | update a record, owner only |
| `DELETE` | `/api/records/{id}` | token | delete a record, owner only |
| `GET` | `/api/prism` | token | caller's records, filtered by emotion |
| `GET` | `/api/dashboard/stats` | token | three chart datasets |

**Filters.** `/api/records` accepts `search`, `emotions[]`, `category_id`, `tier_id`,
`order` and `page`. `/api/prism` accepts `emotions[]` and `order`. `/api/blooming-meadow`
accepts `category_id` and `emotions[]`. Page size is 20 for records and prism, 15 for the
meadow.

**Analytics.** `/api/dashboard/stats` takes `time_range` of `all_time`, `last_month` or
`last_six_months`, and returns three pre-shaped datasets: `spider` (distribution per
emotion), `pie` (share per category) and `area` (volume per month, stacked by tier). The
data arrives ready to plot, with no client-side aggregation.

### Resources

`RecordResource` serialises a JSON:API-inspired envelope of `data`, `links` and `meta`:

```json
{
  "id": "1",
  "attributes": {
    "title": "Finished my thesis",
    "date": "2026-07-14T00:00:00.000000Z",
    "category ": "Studies",
    "tier": "epic breakthrough",
    "visibility": "private",
    "emotions": [{ "id": 1, "name": "Proud", "color": "#4a90d9" }]
  },
  "relationships": {
    "user": { "id": "3", "user_name": "Ophelia", "email": "ophelia@example.com" }
  }
}
```

Attributes are separated from relationships, so a client renders a record from
`attributes` and resolves ownership from `relationships`. `EmotionResource` returns
`{ id, name, color }`. `date` is a full ISO-8601 timestamp and `visibility` serialises as
its backing string.

`RecordResource` dereferences four relations, so queries rendering it eager-load them
together:

```php
->with(['category', 'tier', 'user', 'emotions'])
```

---

## Testing

```sh
composer test                  # config:clear && artisan test
php artisan test --filter=AuthenticationTest

vendor/bin/pint                # format
vendor/bin/pint --test         # check only
```

The suite runs against SQLite `:memory:` with array cache, session and queue, keeping runs
fast and self-contained. Authentication and profile flows are covered by feature tests
inherited from the Breeze baseline.

## Project structure

| Path | Contents |
| --- | --- |
| `app/Http/Controllers/Api/` | Record, Meadow, Prism, Dashboard |
| `app/Http/Controllers/Admin/` | User, Record, Category, Tier, Emotion |
| `app/Http/Controllers/Auth/` | 9 Breeze controllers (session auth) |
| `app/Http/Resources/` | `RecordResource`, `EmotionResource` |
| `app/Http/Requests/` | 7 Form Requests |
| `app/Http/Middleware/` | `IsAdmin` |
| `app/Models/` · `app/Enums/` · `app/Traits/` | 6 models · `RecordVisibility` · `HttpResponse` |
| `resources/views/` | 50 Blade views |
| `routes/` | `api.php`, `web.php`, `auth.php`, `console.php` |
| `database/` | 16 migrations, 5 seeders, 2 factories |
| `tests/` | 23 Breeze feature tests |
