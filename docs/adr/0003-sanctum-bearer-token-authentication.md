# 0003. Sanctum bearer token authentication

- **Status:** Accepted
- **Date:** 2026-09-21
- **Area:** Security
- **Affects:** `app/Http/Controllers/AuthController.php`, `routes/api.php`, `app/Models/User.php`

## Context

The API is consumed by a cross-origin SPA
([ADR-0002](0002-decoupled-architecture-with-a-separate-spa.md)). Two mechanisms could
have authenticated it:

**A. Sanctum cookie mode (`statefulApi`).** The SPA requests
`/sanctum/csrf-cookie`, then uses `credentials: 'include'` on every request. Sanctum
authenticates the SPA's session cookie. The token never leaves the server. This is
Sanctum's recommended default for SPAs and it is CSRF-protected out of the box.

**B. Sanctum personal access tokens.** `POST /api/login` returns a
`plainTextToken`; the SPA sends it as `Authorization: Bearer <token>` on every
subsequent request. The token is held in browser storage.

Cookie mode is the more secure of the two and is what `SANCTUM_STATEFUL_DOMAINS` in
`.env` was configured for. It was not used.

`laravel/sanctum: ^4.0` was installed, and the `HasApiTokens` trait is on `User`
(`app/Models/User.php:19`) — but the *token* half of Sanctum, not the *stateful* half.

## Decision

We authenticate the API with **Sanctum personal access tokens**, exposed through a
single hand-written controller, `App\Http\Controllers\AuthController`:

| Method | Route | Behaviour |
| --- | --- | --- |
| `POST /api/register` | public | validates, creates a `User`, returns the user and a fresh token |
| `POST /api/login` | public | `Auth::attempt()`, returns the user and a fresh token |
| `POST /api/logout` | `auth:sanctum` | `Auth::user()->currentAccessToken()->delete()` |

A new token is minted on **every** login and registration. `logout` deletes only the
current token, so a user may hold several live tokens across devices.

Protected routes are grouped once, in `routes/api.php:30-38`, behind
`middleware: ['auth:sanctum']` and name prefix `api.`. Three routes are deliberately
left outside that group: `login`, `register` and `blooming-meadow`.

Session-cookie auth is used **only** for the Blade backoffice, via the Breeze-generated
`auth` middleware on the `web` route group.

## Consequences

### Positive

- Completely stateless from the API's point of view. No CSRF token round-trip, no
  session row read on every request, trivially horizontal-scalable.
- The token is a first-class database row (`personal_access_tokens`), so it can be
  revoked, audited and given a `last_used_at` timestamp.
- The controller is small enough to read in one screen, and the response shape
  (`{user, token}`) is exactly what an SPA client library wants to store on login.
- `Auth::attempt()` is reused from the Breeze login path, so password verification
  behaviour (including `Auth::attempt`'s rehash-on-login) is consistent between web and
  API.

### Negative

- **XSS becomes token theft.** Any script that runs on the SPA's origin can read the
  token from local storage. There is no second factor.
- **No automatic expiry on the client side.** Tokens persist until explicitly deleted.
  The only cleanup is `Schedule::command('sanctum:prune-expired --hours=24')->daily()`
  in `routes/console.php:11`, which only prunes tokens that have an `expires_at` in the
  past — and `createToken()` is called without a second argument, so **no token ever
  gets an expiry** and the prune job is a no-op.
- **Two different auth stacks to reason about.** `Auth::check()` in a Blade view and
  `auth:sanctum` in a controller are backed by different mechanisms entirely. A
  developer who mixes them will get confusing, hard-to-diagnose behaviour.
- `AuthController` is in the root `App\Http\Controllers` namespace, not
  `App\Http\Controllers\Api`, so it is easy to miss when reading the API controllers.

### Neutral / follow-on

- [ADR-0002](0002-decoupled-architecture-with-a-separate-spa.md) — why a cross-origin
  client existed at all.
- [ADR-0011](0011-role-column-and-admin-middleware.md) — what a token does *not*
  authorize.

## Drift

- **The environment is configured for the mechanism that was not chosen.**
  `SANCTUM_STATEFUL_DOMAINS=localhost:5173` is set in both `.env` and `.env.example`,
  but `bootstrap/app.php`'s `withMiddleware()` body is empty, so `$middleware->statefulApi()`
  is never registered. A reader will reasonably conclude cookie auth is in use. It is
  not.
- **Every login mints a new token and none are ever revoked, so the
  `personal_access_tokens` table grows without bound** and a compromised token stays
  valid indefinitely. `prune-expired` does not help because no expiry is ever set.
- **`AuthController::register()` double-encodes nothing but looks like it does.**
  It calls `Hash::make($request->password)` and `User` casts `password => 'hashed'`.
  This is *safe* — Laravel's `hashed` cast is idempotent, it checks
  `Hash::isHashed()` before re-hashing (`HasAttributes.php:1499`) — but the
  `Hash::make()` is redundant and invites a future edit that removes the cast
  assumption.
- **Both auth endpoints return HTTP 200.** `register` creates a user and should return
  `201 Created`; a client cannot distinguish "created" from "read" without inspecting
  the body.
- **Login is not rate-limited on the API path.** `Auth\LoginRequest` (Breeze) throttles
  to 5 attempts for the web login form, but `Api\LoginUserRequest` has no throttling,
  so `POST /api/login` is an unthrottled credential-stuffing target.
- **No `verified` middleware on any API route.** Token authentication bypasses email
  verification entirely, so an API-only user is never asked to verify. See
  [ADR-0005](0005-laravel-breeze-as-the-web-auth-baseline.md) for the related gap on
  the web side.
