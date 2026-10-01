# 0005. Laravel Breeze as the web auth baseline

- **Status:** Accepted
- **Date:** 2026-09-20
- **Area:** Security
- **Affects:** `routes/auth.php`, `app/Http/Controllers/Auth/`, `app/Http/Controllers/ProfileController.php`, `tests/Feature/Auth/`

## Context

Bloom needs login, registration, logout, password reset, email verification, password
confirmation and profile management for both its backoffice and its API. All of this is
framework-level, well-trodden, and not where the product's novelty lies.

The temptation in a Laravel project is to hand-roll `Auth::login()` calls into
ad-hoc controllers. Doing so reliably produces: no rate limiting on login, no email
verification, no password-reset token flow, no "confirm your password to do a
sensitive action" step, and no session regeneration on login.

## Decision

We installed **`laravel/breeze` (dev dependency, `^2.4`)** with the Blade stack and kept
its output verbatim. It contributes:

| Artefact | Path |
| --- | --- |
| Auth routes | `routes/auth.php` — 13 routes, guest group + auth group |
| Auth controllers | `app/Http/Controllers/Auth/` — 9 controllers |
| Login request | `app/Http/Requests/Auth/LoginRequest.php` (rate limiting, `Lockout` event) |
| Profile | `app/Http/Controllers/ProfileController.php`, `ProfileUpdateRequest` |
| Views | `resources/views/auth/*` (6), `resources/views/profile/*` (4), `layouts/{app,guest}.blade.php` |
| Tests | `tests/Feature/Auth/` (6 files, 18 tests), `tests/Feature/ProfileTest.php` (5 tests) |
| Migrations | `sessions`, `password_reset_tokens` tables |

`laravel/ui` (`^4.6`) is installed alongside it. The profile routes were re-mounted
explicitly in `routes/web.php:25-29` (Breeze normally includes them in
`routes/web.php` itself) and the Breeze dashboard was replaced with a closure
returning `view('dashboard')`.

Breeze covers the **web** side only. The API's auth is a separate hand-written
controller ([ADR-0003](0003-sanctum-bearer-token-authentication.md)) and deliberately
does not reuse Breeze's controllers, because Breeze's contract is redirects and Blade
views, not JSON.

## Consequences

### Positive

- The auth surface is a well-known, heavily reviewed implementation. Login is
  rate-limited to 5 attempts (`LoginRequest::authenticate()`), the password is
  re-hashed on login if the bcrypt cost has changed, and the session is regenerated.
- 23 tests came for free and are the *only* tests covering application code
  ([ADR-0017](0017-phpunit-feature-tests-from-the-breeze-baseline.md)).
- Breeze's views match the app's Blade/Tailwind stack, so they need no restyling to
  look native — they were later restyled to the custom theme, but structurally they
  are Breeze's.
- If the auth layer is ever replaced, the blast radius is known: one directory, one
  routes file, one request class.

### Negative

- **The generated code sits next to hand-written code with no distinction between
  them.** `app/Http/Controllers/AuthController.php` (hand-written, JSON) and
  `app/Http/Controllers/Auth/` (Breeze, Blade) are one namespace level apart and are
  easily confused.
- **Breeze's email verification is inert.** The routes and views exist and
  `verified` middleware is applied to `/dashboard` and `/admin/*`, but `User` does not
  implement `MustVerifyEmail` — the import is commented out at
  `app/Models/User.php:5`. See *Drift*.
- Upgrading Breeze is a manual merge, not a dependency bump.
- The stock test suite gives a false sense of coverage: 23 green tests, zero of which
  touch a controller written for Bloom.

### Neutral / follow-on

- [ADR-0003](0003-sanctum-bearer-token-authentication.md) — the separate API auth.
- [ADR-0011](0011-role-column-and-admin-middleware.md) — the `verified` gate that
  Breeze's middleware was meant to provide.
- [ADR-0017](0017-phpunit-feature-tests-from-the-breeze-baseline.md) — the tests.

## Drift

- **`verified` is a no-op, so the `/dashboard` and `/admin` gate it relies on does
  nothing.** `app/Models/User.php:5` has
  `// use Illuminate\Contracts\Auth\MustVerifyEmail;` commented out, so `email_verified_at`
  exists as a column and is cast to a datetime, but is never enforced.
  `EmailVerificationNotificationController` and `VerifyEmailController` are wired and
  reachable; nothing forces anyone through them.
- **Two Breeze views reference routes that do not exist.** Both throw
  `RouteNotFoundException` if reached:
  - `resources/views/auth/verify-email.blade.php:19` posts to
    `route('verification.resend')`. Breeze names that route `verification.send`
    (`routes/auth.php:47`).
  - `resources/views/auth/reset-password.blade.php:11` posts to
    `route('password.update')`, which is `PUT /password` — the "change my password
    while logged in" route. The reset form should post to `password.store`
    (`POST /reset-password`). The visible symptom is that a password reset does not
    complete, because the POST lands on the wrong route and silently does nothing the
    form expects.
- **`resources/views/profile/edit.blade_bk.blade.php`** is a dead backup file left in
  the view tree. It is never rendered.
- `ProfileUpdateRequest` does not define `authorize()`. This is currently harmless —
  Laravel treats a missing `authorize()` as `true` — but it means the form request
  carries no authorization intent.
