# 0013. Form Requests as the validation boundary

- **Status:** Accepted
- **Date:** 2026-09-21
- **Area:** API
- **Affects:** `app/Http/Requests/`, `app/Http/Controllers/Api/RecordController.php`, `app/Http/Controllers/AuthController.php`

## Context

The API accepts user-supplied data that ends up in the database, on disk, and in a JSON
response. Validation has to happen somewhere. In a Laravel project the options are
inline `$request->validate([...])` calls, Form Request classes, or nothing.

The codebase is inconsistent about this, and the inconsistency is the interesting part:
the API controllers and `Admin\UserController` validate; the other four admin
controllers do not ([ADR-0004](0004-blade-backoffice-for-administration.md)).

## Decision

We use **Form Request classes** as the boundary, with one class per action, and rely on
Laravel resolving and running them as controller type-hint parameters:

```php
public function store(StoreRecordRequest $request) { $validated = $request->validated(); }
```

Validation rules live in `rules()`; `authorize()` returns `true` on all of them,
because authorization is handled by route middleware and by the inline ownership
checks ([ADR-0010](0010-ownership-checks-in-controllers-instead-of-policies.md)), not by
the Form Request.

There are seven:

| Class | Used by | Notable rules |
| --- | --- | --- |
| `Api\StoreRecordRequest` | `Api\RecordController::store` **and** `::update` | `image` → `mimes:jpg,jpeg,png,webp`, `max:2048`; `visibility` → `Rule::in`; `category_id`/`tier_id` → `exists`; `emotions.*` → `integer, exists` |
| `Api\StoreUserRequest` | `AuthController::register` | `password` → `confirmed`, `Password::defaults()` |
| `Api\LoginUserRequest` | `AuthController::login` | `email`, `password` → `min:6` |
| `StoreUserRequest` | `Admin\UserController::store` | `role` → `in:admin,user` |
| `UpdateUserRequest` | `Admin\UserController::update` | `email` → `Rule::unique('users')->ignore($this->route('user'))` |
| `ProfileUpdateRequest` | `ProfileController::update` | Breeze's, with `Rule::unique(...)->ignore($this->user()->id)` |
| `Auth\LoginRequest` | Breeze session login | rate limiting, `Lockout` event |

Rules are written as **arrays of strings** rather than pipe-delimited strings, matching
Laravel 11+ style.

Because `shouldRenderJsonWhen` is configured for `api/*` in `bootstrap/app.php`, a
validation failure on an API route returns a 422 JSON body automatically — the handler
never runs.

## Consequences

### Positive

- Rules are named, discoverable, and reusable. `StoreUserRequest` and
  `UpdateUserRequest` make the difference between create and update explicit rather
  than hiding it in an `if`.
- `$request->validated()` returns **only** the validated keys, which makes mass
  assignment safe by construction: a client cannot inject `user_id` or `id` through a
  validated request. This is why `Api\RecordController::store()` can safely spread
  `$validated` into `Record::create()`.
- `exists:categories,id` and `exists:emotions,id` turn invalid foreign keys into a
  clean 422 instead of a database constraint violation.
- `mimes` + `max` on the image rule is the only thing standing between an arbitrary
  upload and the `records` disk.
- `Rule::unique()->ignore($this->route('user'))` is the standard way to let a user keep
  their own email on update — hard to get right inline.

### Negative

- **One class serves two actions.** `StoreRecordRequest` is used for both `store` and
  `update`, and its rules are nearly all `required`. A `PUT /api/records/{id}` that
  changes only the title is rejected with 422 because `description`, `date`,
  `visibility`, `category_id` and `tier_id` are all missing. This is the classic
  store/update request conflation; the class name says `Store` while serving `update`.
- **`min:6` on the API login password is a floor, not the real policy.** The admin
  request uses `min:8` and `Password::defaults()`, so the API accepts passwords the
  backoffice would reject — and `Api\StoreUserRequest` uses `Password::defaults()`,
  which is not `min:6`. The two login and two registration paths have three different
  password policies.
- **`authorize()` is `true` everywhere**, so no Form Request documents or enforces an
  authorization intent. Authorization is scattered across middleware and controller
  bodies instead ([ADR-0010](0010-ownership-checks-in-controllers-instead-of-policies.md)).
- **Rules are not tied to the schema.** `title` is validated `max:255` while the column
  is `string(200)` — see *Drift*.
- No Form Request exists for admin categories, tiers, emotions, or records, so
  `$request->all()` flows straight into `Model::update()`.

### Neutral / follow-on

- [ADR-0009](0009-json-api-shaped-api-resources.md) — what happens after validation
  passes.
- [ADR-0011](0011-role-column-and-admin-middleware.md) — where `role` is validated.

## Drift

- **Validation exceeds the column width.** `Api\StoreRecordRequest` validates
  `title` as `max:255`, but `records.title` is `string(200)`. MySQL in strict mode
  rejects the insert with a data-truncation error (a 500, not a 422); in
  non-strict mode it silently truncates. Either way the client is not told.
- **A `PUT` is effectively unusable.** Because `StoreRecordRequest` marks
  `description`, `date`, `visibility`, `category_id` and `tier_id` as `required`, and
  is used unmodified for `update()`, a partial update always 422s. Clients must send
  the full representation on every write, which is unstated contract.
- **`Api\LoginUserRequest` has `min:6` while `Api\StoreUserRequest` requires
  `Password::defaults()`** (8 characters by default). Registration is stricter than
  login, which is defensible, but the `min:6` in the login rule is arbitrary — it is a
  format check, not an authentication rule, and it reveals nothing useful.
- **`Api\LoginUserRequest` performs no rate limiting**, unlike Breeze's
  `Auth\LoginRequest` ([ADR-0003](0003-sanctum-bearer-token-authentication.md)).
- **The `image` rule is validated but the file is not always moved or removed from
  `$validated`.** `Api\RecordController::store()` intends to `unset($validated['image'])`
  but calls `$unset(...)` — an undefined variable holding a function-call expression —
  which is a fatal error, not a language construct. See *Drift* in
  [technical-debt.md](../technical-debt.md).
- **`Api\StoreUserRequest` requires `password` to be `confirmed`**, so the API client
  must send `password` *and* `password_confirmation`. This is not documented anywhere
  outside the rules array.
