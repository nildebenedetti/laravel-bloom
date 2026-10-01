# Data model

Seven application tables, two join tables, and the framework's supporting tables. This
document is the column-level reference; see [ADRs 0001, 0006, 0007, 0008](../adr/README.md)
for why the shape is what it is.

An ER diagram is in [`../database/laravel-bloom-ER-diagram-14092026.webp`](../database/laravel-bloom-ER-diagram-14092026.webp).

## Entity relationships

```
                        ┌──────────────┐
                        │    users     │
                        │──────────────│
                        │ id           │
                        │ name         │
                        │ email  (uniq)│
                        │ email_verified_at
                        │ role         │  'admin' | 'user'
                        │ password     │
                        │ remember_token
                        └──┬────────┬──┘
             1:1 hasOne   │        │  1:N hasMany
                           │        │
                ┌──────────▼──┐     │
                │ user_profiles│     │
                │──────────────│     │
                │ id           │     │
                │ user_id (uniq)│    │
                │ bio   (null) │     │
                └──────────────┘     │
                                     │
                                     ▼
┌──────────────┐  N:1  ┌──────────────────────────────────┐  N:1  ┌──────────────┐
│  categories  │◀──────│             records              │──────▶│    tiers     │
│──────────────│       │──────────────────────────────────│       │──────────────│
│ id           │       │ id                              │       │ id           │
│ name     (80)│       │ title        (200)               │       │ name     (80)│
│ description  │       │ description   (text)             │       │ description  │
│ timestamps   │       │ date         (date)              │       │   (255, null)│
└──────────────┘       │ image_path   (text, null)         │       │ timestamps   │
                       │ image_alt    (255, null)          │       └──────────────┘
                       │ visibility  enum('public',        │
                       │             'private') def 'private'
                       │ category_id (FK, null)  ──────────┘
                       │ tier_id     (FK, null)
                       │ user_id     (FK, null, NOT ENFORCED)
                       │ created_at / updated_at
                       └────────┬──────────────────┐
                                │ N:M
                    ┌───────────▼──────────┐
                    │    emotion_record    │
                    │──────────────────────│
                    │ id                   │
                    │ emotion_id  (FK)     │  → emotions.id
                    │ record_id   (FK)     │  → records.id
                    │ timestamps           │
                    │ both FKs:            │
                    │   cascadeOnDelete()  │
                    └───────────┬──────────┘
                                │ N:M
                    ┌───────────▼──────────┐        ┌─────────────────────────┐
                    │      emotions        │        │  personal_access_tokens │
                    │──────────────────────│        │─────────────────────────│
                    │ id                   │        │ id                      │
                    │ name             (70) │        │ tokenable_type (morphs) │
                    │ color            (7)  │        │ tokenable_id   (morphs) │
                    │ timestamps           │        │ name                    │
                    └──────────────────────┘        │ token        (64, uniq) │
                                                 │ abilities  (text, null) │
                        ┌────────────────────────┐│ last_used_at (ts, null) │
                        │  personal_access_tokens││ expires_at   (ts, null) │
                        └────────────────────────┘└─────────────────────────┘
                                                 ▲
                                                 └── hasMany via HasApiTokens
                                                   (morph → users)
```

## Tables

### `records` — the core aggregate

| Column | Type | Null | Default | Notes |
| --- | --- | --- | --- | --- |
| `id` | bigint unsigned | no | auto | |
| `title` | varchar(200) | no | | **validated as `max:255`** on the API — see drift below |
| `description` | text | no | | |
| `date` | date | no | | the *event* date, not the creation date. Cast to Carbon. `created_at` is used for Meadow ordering |
| `image_path` | text | yes | | relative path under `records/` on the `public` disk |
| `image_alt` | varchar(255) | yes | | accessibility text, added post-hoc |
| `visibility` | enum(`public`,`private`) | no | `private` | cast to `RecordVisibility`. Drives the Blooming Meadow |
| `category_id` | bigint unsigned | yes | | FK → `categories.id`, `constrained()` |
| `tier_id` | bigint unsigned | yes | | FK → `tiers.id`, `constrained()` |
| `user_id` | bigint unsigned | yes | | **column exists, FK constraint does not** |
| `created_at` / `updated_at` | timestamp | yes | | |

`description` is `NOT NULL` with no default, so every insert must supply it. The API
marks it `nullable`; the backoffice has no validation at all.

**`user_id` is constrained, but the intent was not.** `2026_09_21_150015_add_user_id_column_to_records_table.php`:

```php
$table->foreignId('user_id')->nullable()->constrained()->dropForeign();
//  ^ creates the constraint   ^ and silently does nothing
```

`ForeignIdColumnDefinition` has no `dropForeign()`, so the last call is absorbed by
`Fluent::__call` as a plain attribute. The constraint **is** created — verified against a
migrated database as `foreign key("user_id") references "users"("id")` — with no
`ON DELETE` clause, so deleting a user who owns records raises an integrity violation
rather than cascading. The migration's `down()` also cannot reverse it on SQLite
(`dropForeign` by name is unsupported there), which is the driver the test suite uses.

Records created through the backoffice still have `user_id = NULL`: the column is
nullable and `Admin\RecordController::store()` never assigns it. See
[td-09](../technical-debt.md#td-09).

**Ordering is inconsistent by intent.** `MeadowController` orders by `created_at desc`;
`Api\RecordController` and `PrismController` order by `date`. So the public feed is
chronological by *posting* and the private views are chronological by *occurrence*.

### `categories` — taxonomy

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | bigint unsigned | no | |
| `name` | varchar(80) | no | **not unique** |
| `description` | varchar(255) | **no** | |
| `created_at` / `updated_at` | timestamp | yes | |

Seeded with 12: Career, Studies, Bonds, Sports, Cooking, Crafting, Wellness, Travel,
Finance, Languages, Culture, Promises.

No casts, no scopes, no `HasFactory`.

### `tiers` — taxonomy

Structurally identical to `categories`, except `description` is **nullable**.

Seeded with 4, in ascending order of significance: small win, solid step, major
milestone, epic breakthrough. The seed order is meaningful — `RecordsTableSeeder` picks
tiers with `rand(1, 4)`, so tier ids double as a rank.

No casts, no scopes, no `HasFactory`.

### `emotions` — taxonomy with colour

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | bigint unsigned | no | |
| `name` | varchar(70) | no | **not unique** |
| `color` | varchar(7) | no | hex colour, generated with `$faker->hexColor()` |
| `created_at` / `updated_at` | timestamp | yes | |

Seeded with 7: Proud, Relieved, Excited, Determined, Grateful, Grounded, Cherished.

Many-to-many with `records` via `belongsToMany`.

### `emotion_record` — pivot

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | bigint unsigned | no | |
| `emotion_id` | bigint unsigned | no | FK → `emotions.id`, `cascadeOnDelete()` |
| `record_id` | bigint unsigned | no | FK → `records.id`, `cascadeOnDelete()` |
| `created_at` / `updated_at` | timestamp | yes | never updated by `sync()` |

**No unique index on `(emotion_id, record_id)`.** Nothing at the database level
prevents a duplicate pairing; only `sync()` keeps it clean.

### `users`

| Column | Type | Null | Default | Notes |
| --- | --- | --- | --- | --- |
| `id` | bigint unsigned | no | auto | |
| `name` | varchar(255) | no | | |
| `email` | varchar(255) | no | | unique |
| `role` | varchar(255) | no | `user` | **not constrained** to `admin`/`user` at the DB level. `after('email')` |
| `email_verified_at` | timestamp | yes | | cast to datetime; never enforced |
| `password` | varchar(255) | no | | cast `hashed` (idempotent) |
| `remember_token` | varchar(100) | yes | | hidden |
| `created_at` / `updated_at` | timestamp | yes | | |

### `user_profiles`

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| `id` | bigint unsigned | no | |
| `user_id` | bigint unsigned | no | **unique**, FK → `users.id`, `cascadeOnDelete()` |
| `bio` | text | yes | |
| `created_at` / `updated_at` | timestamp | yes | |

A `hasOne` relation, so a user has at most one profile. Created on demand by
`Admin\UserController` and maintained with `updateOrCreate()`.

### `personal_access_tokens` — Sanctum

Standard Sanctum schema. `tokenable` is a morph, so the same table could hold tokens for
any model. `expires_at` exists but **`createToken()` is never called with an expiry**, so the
column stays `null` and the `sanctum:prune-expired` daily schedule is a no-op.

Tokens are nevertheless **not valid forever**: `config/sanctum.php` sets `expiration` to
4320 minutes, and Sanctum's `Guard` enforces that global limit against `created_at` for every
token. Expired tokens stop authenticating but are never removed from the table, so it grows
without bound as users log in repeatedly.

## Framework tables

Created by the `0001_01_01_*` migrations, unmodified:

| Table | Used by |
| --- | --- |
| `sessions` | `SESSION_DRIVER=database` — backoffice sessions |
| `password_reset_tokens` | Breeze password reset flow |
| `cache`, `cache_locks` | `CACHE_STORE=database` |
| `jobs`, `job_batches`, `failed_jobs` | `QUEUE_CONNECTION=database`. **No jobs are ever dispatched** |

## Relationships in code

```php
// Record
public function category() { return $this->belongsTo(Category::class); }
public function tier()     { return $this->belongsTo(Tier::class); }
public function user()     { return $this->belongsTo(User::class); }
public function emotions() { return $this->belongsToMany(Emotion::class); }   // → emotion_record

protected function casts(): array
{
    return ['visibility' => RecordVisibility::class, 'date' => 'date'];
}

// User
public function profile()  { return $this->hasOne(UserProfile::class); }
public function records()  { return $this->hasMany(Record::class); }
public function isAdmin(): bool { return $this->role === 'admin'; }

// Category / Tier
public function records() { return $this->hasMany(Record::class); }

// Emotion
public function records() { return $this->belongsToMany(Record::class); }

// UserProfile
public function user() { return $this->belongsTo(User::class); }
```

`User` also uses `HasApiTokens` (Sanctum), `HasFactory`, and `Notifiable`. `Record`
uses `HasFactory`. `Category`, `Tier`, `Emotion` and `UserProfile` use no traits at all.

## Eager loading requirements

`RecordResource` dereferences four relations:

```php
'category ' => $this->category?->name,
'tier'      => $this->tier?->name,
'emotions'  => EmotionResource::collection($this->whenLoaded('emotions')),
'user'      => [ 'id' => $this->user?->id, 'user name' => …, 'user email' => … ],
```

So **any query that renders a `RecordResource` must eager-load `user`**, or it is N+1.
Current state:

| Endpoint / view | Eager loads | Missing | Queries per page |
| --- | --- | --- | --- |
| `GET /api/records` | category, tier, emotions | `user` | ~1 + 20 + 20 = **41** |
| `GET /api/blooming-meadow` | *(none)* | all four | **61** for 15 records |
| `GET /api/prism` | `emotions` (post-pagination) | category, tier, user | **~81** for 20 records |
| `/admin/records` | *(none)* | `user` | 1 + N |

Fixing all four is a one-line change per endpoint:
`->with(['category', 'tier', 'user', 'emotions'])`.

## Enumerations

`RecordVisibility` is the only enum:

```php
enum RecordVisibility: string
{
    case PUBLIC  = 'public';
    case PRIVATE = 'private';

    public function label() {
        return match ($this) {
            self::PUBLIC  => 'Public',
            self::PRIVATE => 'Private',
        };
    }
}
```

`users.role` is a bare string with no enum, no cast, and no DB constraint — the only
other authorization-relevant value in the schema.

## Schema drift worth knowing about

| Issue | Where |
| --- | --- |
| `records.user_id` has no FK constraint, and its `down()` will fail | `2026_09_21_150015` |
| `title` is `varchar(200)`, validated as `max:255` | migration vs `Api\StoreRecordRequest` |
| `categories.description` NOT NULL, `tiers.description` nullable | the two taxonomy migrations |
| `name` is not unique on `categories`, `tiers` or `emotions` | no unique index |
| `emotion_record` has no unique `(emotion_id, record_id)` | pivot migration |
| `records.user_id` is never set by the backoffice | `Admin\RecordController::store()` |
| `database/database.sqlite` is committed and drifting | see [ADR-0012](../adr/0012-mysql-as-the-primary-datastore.md) |

Full list with impact and fixes: [technical-debt.md](../technical-debt.md).
