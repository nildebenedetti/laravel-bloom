# 0004. Blade backoffice for administration

- **Status:** Accepted
- **Date:** 2026-09-16
- **Area:** Frontend
- **Affects:** `routes/web.php`, `app/Http/Controllers/Admin/`, `resources/views/`, `package.json`

## Context

Bloom needs an administrative surface to manage the controlled vocabularies that the
public product depends on: categories, tiers and emotions. Adding a tier is a content
task, not a feature, and it has to be possible without a deploy.

The same repository also hosts the SPA-consuming API
([ADR-0002](0002-decoupled-architecture-with-a-separate-spa.md)). The obvious Laravel
answers for the admin surface were:

| Option | Cost |
| --- | --- |
| Inertia + Vue/React | A second SPA in this repo, plus a build pipeline for it |
| Livewire | A new component model, plus a runtime dependency in the Blade layout |
| Extend the external React SPA with admin screens | The admin team must learn and ship a React app; admin work blocks on the SPA's release cycle |
| **Server-rendered Blade** | No new runtime; the framework's default |

Since the admin surface is internal, low-traffic, and form-heavy, none of its pages
need client-side interactivity beyond a navbar collapse and a few confirm dialogs.

## Decision

We render the administration UI as **server-side Blade views**, one resource
controller per entity, mounted in `routes/web.php:31-41` behind
`['auth', 'verified', IsAdmin::class]` under the `/admin` prefix:

```php
Route::middleware('auth', 'verified', IsAdmin::class)
        ->prefix('admin')
        ->group( function () {
    Route::resource('/users', UserController::class);
    Route::resource('/records', RecordController::class)->names('admin.records');
    Route::resource('/categories', CategoryController::class);
    Route::resource('/tiers', TierController::class);
    Route::resource('/emotions', EmotionController::class);
});
```

Styling is **Bootstrap 5 + Bootstrap Icons**, compiled by Vite via
`laravel-vite-plugin`, with **Tailwind CSS 4** also installed and layered on top
(`resources/css/app.css`). Component and layout conventions:

- One layout Blade component per entity (`layouts/{records,categories,tiers,emotions,users}.blade.php`)
- Shared `partials/header.blade.php` (fixed-top glass navbar) and `partials/footer.blade.php`
- Two reusable components: `components/record-card.blade.php`, `components/page-access-card.blade.php`
- 50 Blade files total; no JS framework is registered in the layout

Admin controllers live in `App\Http\Controllers\Admin\` and return
`view(...)` / `redirect()->route(...)` — no serialization, no Resources.

## Consequences

### Positive

- Zero extra runtime. The admin surface works with nothing but `laravel/framework`.
- No build step to break it. It renders from the same PHP process that serves the API.
- Consistent with the rest of the `web` group: session auth, CSRF, Breeze's `auth`
  and `verified` middleware, and Blade error bags all work unchanged.
- Content edits are visible on refresh — no optimistic UI to get wrong when adding a
  tier.

### Negative

- **The admin surface is a second, hand-maintained UI for the same entities the API
  exposes.** Any field added to `records` must be added to both
  `resources/views/records/create.blade.php` and `StoreRecordRequest`.
- **Five near-identical CRUD controllers** with substantially duplicated logic. See
  *Drift*.
- **The admin and API paths for the same entity validate differently**, so an entity
  can be creatable through one and not the other.
- Two CSS frameworks (Bootstrap and Tailwind) coexist, with custom utility classes
  like `.btn-lightblue` and `.glass-bar` defined by hand in `app.css`. There is no
  design system to consult.
- Bootstrap's JS is imported in `resources/js/app.js` for the navbar collapse, so the
  page is not fully progressive-enhancement-free.

### Neutral / follow-on

- [ADR-0005](0005-laravel-breeze-as-the-web-auth-baseline.md) — where the auth views
  and layouts came from.
- [ADR-0011](0011-role-column-and-admin-middleware.md) — the `IsAdmin` gate.
- [ADR-0013](0013-form-requests-as-the-validation-boundary.md) — why the admin
  controllers are the exception to the validation boundary.

## Drift

- **The admin controllers bypass the validation boundary entirely.** Four of the five
  take a bare `Illuminate\Http\Request` and call `$request->all()`. `Admin\UserController`
  is the sole exception, using `StoreUserRequest` / `UpdateUserRequest` and
  `$request->validated()`. See [ADR-0013](0013-form-requests-as-the-validation-boundary.md).
- **Route naming is inconsistent within the same group.** `records` is renamed to
  `admin.records.*` while `users`, `categories`, `tiers` and `emotions` keep their bare
  names, so the URL prefix says `/admin` and the route name says `users.index`. This
  caused a real navigation bug — see `technical-debt.md`.
- **`Admin\RecordController::index()` is an N+1 factory.** It calls
  `Record::latest('date')->get()` with no eager load, and
  `resources/views/components/record-card.blade.php:11` reads `$record->user?->name` for
  every row. The admin record list issues one query per record.
- **The admin records form does not collect an owner.** `store()` never sets
  `user_id`, so every backoffice-created record is ownerless and unreachable from the
  owner's API collection. See [ADR-0001](0001-record-as-the-core-aggregate.md).
- **The navbar labels for two links are swapped.** `partials/header.blade.php:21`
  renders `route('categories.index')` with the text `Users`, and line 27 renders
  `route('users.index')` with the text `Categories`.
