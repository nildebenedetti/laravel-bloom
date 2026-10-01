# Setup & contribution guide

Everything needed to get Bloom running locally, plus the conventions to follow when
changing it.

## Requirements

| Tool | Version | Check |
| --- | --- | --- |
| PHP | ^8.3 | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL | 8.x (or SQLite, with caveats) | `mysql --version` |
| Node | 20+ | `node -v` |
| pnpm | 9+ | `pnpm -v` |

The package manager is **pnpm** (`pnpm-lock.yaml`, `pnpm-workspace.yaml`). `composer.json`'s
`setup` script still calls `npm install --ignore-scripts` and `npm run build` — that
works, but it bypasses the lockfile. Prefer `pnpm install` locally.

## Install

```sh
git clone <repo-url> laravel-bloom
cd laravel-bloom

composer install
cp .env.example .env          # then edit, see below
php artisan key:generate
```

`composer install` runs `php artisan package:discover` automatically via
`post-autoload-dump`.

### Database

The app runs on **MySQL**; `.env.example` ships SQLite. Pick one deliberately:

**MySQL (matches how the app was developed):**

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
php artisan db:seed
```

The seeder is **not idempotent** — running it twice duplicates every row and violates the
unique constraint on `users.email`. Use `php artisan migrate:fresh --seed` instead.

> ⚠️ `php artisan db:seed` creates two accounts with published passwords:
> `admin@bloom.org` / `safepsw@bloom2026` (role `admin`) and
> `offHell@live.com` / `password123` (role `user`). Change or remove them before this
> database goes anywhere near a shared environment.

**SQLite:**

```sh
touch database/database.sqlite      # already committed, but check it
```

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

> ⚠️ **SQLite will break the dashboard endpoint.** `GET /api/dashboard/stats` uses
> `DATE_FORMAT()`, which only exists in MySQL. Everything else works. See
> [ADR-0012](../adr/0012-mysql-as-the-primary-datastore.md).

### Frontend assets

The backoffice's CSS and JS are compiled by Vite:

```sh
pnpm install
pnpm run build        # → public/build
# or, for hot reload:
pnpm run dev
```

> ⚠️ **Delete `public/hot` before deploying.** It is committed and contains
> `http://[::1]:5173`. While it exists, `Vite::asset()` emits dev-server URLs and every
> compiled asset 404s. `php artisan optimize:clear` does not remove it.

### The React SPA

The SPA is a **separate repository** and is not here. To develop against this API you
need it running on `http://localhost:5173` — the only origin `config/cors.php` allows.
See [ADR-0002](../adr/0002-decoupled-architecture-with-a-separate-spa.md).

## Running

```sh
php artisan serve          # http://localhost:8000
pnpm run dev               # Vite, if you are working on the backoffice's styles
```

Seeded admin credentials: `admin@bloom.org` / `safepsw@bloom2026`. Log in at
`http://localhost:8000/login`, then visit `/admin/records`.

### Scheduled work

`routes/console.php` schedules one job:

```sh
php artisan sanctum:prune-expired --hours=24     # daily
```

It currently removes nothing, because no token is ever created with an `expires_at` — the
column this command filters on stays `null`. Tokens are still rejected after 72 hours by the
global `expiration` in `config/sanctum.php`
([ADR-0003](../adr/0003-sanctum-bearer-token-authentication.md)). To run scheduled work
locally, add to `bootstrap/app.php`:

```php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('sanctum:prune-expired --hours=24')->daily();
})
```

or just `php artisan schedule:work`.

## Tests

```sh
composer test              # config:clear then artisan test
php artisan test
php artisan test --filter=AuthenticationTest
```

The suite runs against **SQLite `:memory:`** with array cache/session/queue and
`BCRYPT_ROUNDS=4` — fast and self-contained, but it means **the code paths that only
work on MySQL are never exercised.** `GET /api/dashboard/stats` cannot be tested as the
suite stands.

All 23 existing tests came from `laravel/breeze` and cover auth and profile flows. There
are **no tests for any Bloom code** — no model, controller, Form Request, Resource,
middleware or seeder. If you add a test for application code you will be the first.

Adding a feature test:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_meadow_lists_only_public_records(): void
    {
        $owner = User::factory()->create();

        Record::factory()->create(['user_id' => $owner->id, 'visibility' => 'public']);
        Record::factory()->create(['user_id' => $owner->id, 'visibility' => 'private']);

        $this->getJson('/api/blooming-meadow')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
```

`tests/TestCase.php` has **no helper methods**. There is no `actingAsApiUser()`, so
token-authenticated requests need the boilerplate:

```php
$user  = User::factory()->create();
$token = $user->createToken('test')->plainTextToken;

$this->withHeader('Authorization', "Bearer {$token}")
     ->getJson('/api/records')
     ->assertOk();
```

That helper is the first thing worth extracting into `TestCase`.

> `RecordFactory` calls `Category::factory()` and `Tier::factory()`, which **do not
> exist** — those models lack the `HasFactory` trait. The factory only works if the
> categories and tiers tables are already populated. Seed first, or fix the factory
> before relying on it.

## Code style

`laravel/pint` is installed:

```sh
vendor/bin/pint           # fix
vendor/bin/pint --test    # check only
```

There is **no static analysis** — no PHPStan, no Larastan, no Psalm. This is why
`$unset($validated['image'])` and two bare `error()` calls survive in the API
controller. Adding `larastan` at level 5 would flag every item in
[technical-debt.md](../technical-debt.md) in this directory. It is the single highest-value
change you could make to this project.

## Conventions to follow

### Routes

- API routes go in `routes/api.php`; authenticated ones inside the
  `Route::group(['as' => 'api.', 'middleware' => ['auth:sanctum']], …)` block.
- Backoffice routes go inside the `/admin` group in `routes/web.php`, which already
  applies `auth`, `verified` and `IsAdmin`. A new admin route inherits all three.
- **Use the `admin.` name prefix for new admin resources.** `records` already does;
  `users`, `categories`, `tiers` and `emotions` do not. Do not copy that inconsistency
  forward.

### Controllers

- `App\Http\Controllers\Api\*` returns JSON — use `RecordResource`.
- `App\Http\Controllers\Admin\*` returns Blade — no Resource layer needed.
- Keep authorization out of route files and **in a policy**, or at minimum in one place
  per action. Do not add a fourth copy of
  `if (Auth::user()->id !== $record->user_id)`.
- Prefer `$request->user()` over the `Auth` facade. The facade reads the default guard,
  which makes the code untestable with `actingAs()` on a different guard.

### Validation

- Every write path gets a Form Request. The API already does this; the admin
  controllers for records, categories, tiers and emotions **do not**. Adding a new
  admin write path without one repeats a known bug.
- Use **separate** `StoreXRequest` and `UpdateXRequest` classes. `Api\StoreRecordRequest`
  serving both is why `PUT /api/records/{id}` rejects every partial update.
- Keep rules in sync with the column widths. `title` is `string(200)` and validated
  `max:255`.
- Use `RecordVisibility::cases()` rather than re-typing `['public', 'private']` in a
  `Rule::in`.

### Models

- Declare the fillable list **once**. `User` currently has both a `#[Fillable]`
  attribute and a `$fillable` property that disagree; the property wins.
- Keep `role` out of `$fillable` unless a write genuinely needs it, and never combine
  that with `$request->all()`.
- Use the relationship for ownership checks (`$record->user()->is($request->user())`),
  not an id comparison.
- Add `HasFactory` if you intend to write tests — `Category`, `Tier` and `Emotion` all
  lack it, which is why `RecordFactory` is half-broken.

### Queries

`RecordResource` reads four relations. **Any query that renders records must eager-load
all four:**

```php
->with(['category', 'tier', 'user', 'emotions'])
```

Forgetting `user` alone is enough to reintroduce an N+1. See
[technical-debt.md](../technical-debt.md#td-05).

### Enums and config

- Reference `RecordVisibility::PUBLIC`, not the string `'public'`.
- Read origins from `env('FRONTEND_URL')` in `config/cors.php` rather than hardcoding.
- Register middleware aliases in `bootstrap/app.php` so routes can use short names.

### Frontend

- Bootstrap 5 is the base. `.btn-lightblue`, `.glass-bar` and `.flowerized` are custom
  classes in `resources/css/app.css`; prefer them over new inline styles.
- Use `<x-record-card>` and `<x-page-access-card>` rather than new card markup.
- Do not add a JS framework to this repository. The SPA lives elsewhere
  ([ADR-0002](../adr/0002-decoupled-architecture-with-a-separate-spa.md)).

## Before you open a pull request

```sh
vendor/bin/pint --test
composer test
```

And a checklist for the change you are actually making:

- [ ] Does it add a Form Request, or is the write path already unvalidated?
- [ ] Does it add a query that renders records? Are all four relations eager-loaded?
- [ ] Does it add a route? Is it in the right group with the right name prefix?
- [ ] Does it change `RecordResource`? **The SPA needs a matching change** — it is in
      another repository and will break at runtime, not at build time.
- [ ] Does it change a migration? Is `down()` correct? Do the column widths match the
      validation rules?
- [ ] Is there a test? Any test is better than the 23 Breeze ones you currently have.

## Where things are

| Path | Contents |
| --- | --- |
| `app/Models/` | 6 models |
| `app/Enums/` | `RecordVisibility` |
| `app/Traits/` | `HttpResponse` |
| `app/Http/Controllers/Api/` | 4 controllers (Records, Meadow, Prism, Dashboard) |
| `app/Http/Controllers/Admin/` | 5 controllers |
| `app/Http/Controllers/Auth/` | 9 Breeze controllers |
| `app/Http/Requests/` | 7 Form Requests |
| `app/Http/Resources/` | `RecordResource`, `EmotionResource` |
| `app/Http/Middleware/` | `IsAdmin` |
| `routes/` | `api.php`, `web.php`, `auth.php` (Breeze), `console.php` |
| `database/migrations/` | 16 migrations |
| `database/seeders/` | `DatabaseSeeder` + 5 |
| `database/factories/` | `UserFactory`, `RecordFactory` |
| `resources/views/` | 50 Blade files |
| `tests/` | 23 Breeze tests, no application tests |
| `docs/` | this documentation |

## Further reading

- [Architecture overview](../architecture/overview.md)
- [Data model](../architecture/data-model.md)
- [API reference](../api/reference.md)
- [Technical debt](../technical-debt.md) — what's broken, and what to fix first
- [ADRs](../adr/README.md) — why the system is shaped this way
