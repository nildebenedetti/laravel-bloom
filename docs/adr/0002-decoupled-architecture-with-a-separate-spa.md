# 0002. Decoupled architecture with a separate SPA

- **Status:** Accepted
- **Date:** 2026-09-14
- **Area:** Architecture
- **Affects:** `routes/api.php`, `config/cors.php`, `.env.example`

## Context

The product brief describes two very different experiences:

- **Blooming Meadow** — a public, unauthenticated, read-only stream of other people's
  public records. Social, visual, infinite-scroll.
- **The private suite** — manage your own records, explore them by emotion, and
  analyse your own progress with charts.

These have very different interaction models, and very different performance
profiles. The Meadow is a feed: heavy on lazy loading, images, infinite scroll, and
third-party charting libraries. The private suite is a dashboard: heavy on aggregate
queries and precise layout.

Serving both from the same Laravel render loop would mean either shipping a large JS
bundle to a visitor who only wants to browse, or building a lightweight feed in Blade
and a rich dashboard in JavaScript anyway — two implementations of the same data.

The obvious answer for a Laravel team is Inertia or Livewire: one codebase, one
render target, no CORS. It was available and it was not chosen.

## Decision

We split the system across **two separate repositories**:

1. **This repository** — the Laravel 13 backend. It owns the database and exposes
   two distinct surfaces:
   - a JSON REST API under `/api/*` (`routes/api.php`), consumed by the SPA
   - a server-rendered Blade "backoffice" under `/admin/*` and `/dashboard`
     (`routes/web.php`), used for administration
2. **A separate React SPA repository** — not in this repository. It runs on Vite's dev
   server at `http://localhost:5173` and talks to the API at
   `http://localhost:8000/api`.

The two are joined only by HTTP. This repo contains no React, no JS component for the
SPA, and no Inertia/Livewire/Vue dependency. The only frontend build here is Vite
driven by `laravel-vite-plugin` compiling `resources/css/app.css` and
`resources/js/app.js` — which load Bootstrap 5 and Bootstrap Icons for the Blade
backoffice. `package.json` has no `axios` and no charting library, because the SPA owns
those.

`config/cors.php` exists purely to serve this split: it allows
`http://localhost:5173` against `api/*`.

## Consequences

### Positive

- The SPA can be built, tested, hot-reloaded and deployed independently of PHP. No
  Blade/JS coupling, no shared `node_modules`, no Vite manifest negotiation.
- A visitor on the Meadow downloads only what the SPA needs; the backoffice's
  Bootstrap bundle is irrelevant to them.
- The API is a real, enforceable contract. If it were only ever consumed by a
  server-side Blade view, there would be no pressure to add Resources, Form Requests
  or consistent status codes — the view would tolerate anything.
- Public and private surfaces can diverge freely. The Meadow exposes a different
  resource with different authorization rules than the owner's own list.

### Negative

- **Two auth strategies must coexist.** Session cookies for the backoffice, bearer
  tokens for the API. This is the single largest source of confusion in the codebase
  and forced [ADR-0003](0003-sanctum-bearer-token-authentication.md).
- **CSRF protection and CORS interact.** Anything with `supports_credentials` needs
  careful origin allowlisting. See *Drift*.
- **The API contract is unenforced by types on both sides.** A field rename in
  `RecordResource` breaks the SPA at runtime, not at build time.
- **Local development requires two processes** (plus a database) and a fixed port
  contract.
- Someone reading only this repo cannot see the primary UI. The README is the only
  place the two halves are described together.

### Neutral / follow-on

- [ADR-0003](0003-sanctum-bearer-token-authentication.md) — how the SPA authenticates.
- [ADR-0004](0004-blade-backoffice-for-administration.md) — why the backoffice stayed Blade.
- [ADR-0009](0009-json-api-shaped-api-resources.md) — the serialization contract.

## Drift

- **The CORS origin is hardcoded, ignoring the environment variable that exists to
  configure it.** `.env.example` ships `FRONTEND_URL=http://localhost:5173`, and
  `.env` sets it, but `config/cors.php:25-27` reads neither — it hardcodes
  `'allowed_origins' => ['http://localhost:5173']`. Pointing the SPA at any other host
  (a colleague's machine, a preview deployment, a tunnel) fails the preflight with no
  error message that points at the actual cause.
- **`max_age` is `0` and `supports_credentials` is `true`**, so every preflight is
  re-negotiated and the browser is told to cache nothing. Fine for dev, wasteful, and
  the credential support is currently unused because the SPA authenticates with a
  bearer token rather than a cookie.
- **`SANCTUM_STATEFUL_DOMAINS=localhost:5173` is set in both `.env` and
  `.env.example` but does nothing.** `bootstrap/app.php` has an empty
  `withMiddleware()` body, so `$middleware->statefulApi()` is never called. The SPA
  cannot use Sanctum's cookie mode even though the environment is configured for it.
  The declared intent was Sanctum SPA auth; the implemented mechanism is bearer
  tokens. See [ADR-0003](0003-sanctum-bearer-token-authentication.md).
- **`public/hot` contains `http://[::1]:5173`** and is committed. This is a stale Vite
  dev-server marker. Its presence makes `Vite::asset()` emit dev-server URLs in
  production, so the backoffice's logo and any other compiled asset will 404 for any
  visitor whose `public/hot` matches nothing.
