# 0010. Ownership checks in controllers, not policies

- **Status:** Accepted
- **Date:** 2026-09-22
- **Area:** Security
- **Affects:** `app/Http/Controllers/Api/RecordController.php`, `app/Http/Controllers/Admin/`

## Context

A record belongs to exactly one user ([ADR-0001](0001-record-as-the-core-aggregate.md)).
The API exposes `show`, `update` and `destroy` for `/api/records/{record}`, and Laravel
route-model binding will happily resolve **any** record id for **any** authenticated
user. Without an explicit check, user A can read, overwrite and delete user B's private
journal by guessing an integer.

Laravel's idiomatic answer is a **Policy** class: `RecordPolicy` with `view`, `update`
and `delete` methods, registered in `AuthServiceProvider` or auto-discovered, called
from the controller with `$this->authorize('update', $record)`. Policies give one
place to define and test the rule, and they work for both `Gate::allows()` in Blade
and `$request->user()->can()` in controllers.

There is no `app/Policies/` directory, no `Gate::define`, and no `authorize()` call
anywhere in the application.

## Decision

We enforce ownership with an **inline identity comparison in each controller method**,
using the `auth:sanctum` guard's user:

```php
// app/Http/Controllers/Api/RecordController.php
if (Auth::user()->id !== $record->user_id) {
    return $this->error('', 'you are not authorized to access this record', 403);
}
```

The check is repeated in `show()` (line 52), `update()` (line 97) and `destroy()`
(line 132). The `web` side is protected differently — not by an ownership check at all,
but by route middleware, since the backoffice is admin-only
([ADR-0011](0011-role-column-and-admin-middleware.md)).

## Consequences

### Positive

- No extra class, no registration step, and the rule is visible in the same screen as
  the code it protects. For three methods over one entity, that is a defensible
  trade-off.
- The failure mode is explicit: a 403 with a message, rather than a `403` exception
  page from a gate.
- Nothing to keep in sync with the model's relationships.

### Negative

- **The rule is duplicated three times and already has drifted.** Two of the three
  copies call a bare `error()` instead of `$this->error()` — see *Drift*. A single
  copy is fixed while two are broken, which is exactly the failure mode duplication
  invites.
- **Ownership is expressed as an ID comparison rather than the relationship.** The
  idiomatic form is `$record->user()->is($request->user())`, or `!$request->user()
  ->records()->find($record->id)`. Comparing `Auth::user()->id` reads the user off the
  global `Auth` facade instead of the injected request, so it is not testable with
  `actingAs()` on a non-default guard and will not see a guard-swapped user.
- **No single place to add a new rule.** "Can this user edit this record?" and "can
  this user delete this record?" are answered by two different snippets, so they can
  disagree.
- **There is no `admin` bypass.** An administrator cannot read a record through the
  API, even though the same administrator can read and edit it through the backoffice.
  The two surfaces have different capabilities.
- **Nothing prevents the check from being forgotten on a new endpoint.** A future
  `share()` or `export()` action would ship with no authorization by default, and
  nothing — no policy, no test, no static analysis rule — would notice.

### Neutral / follow-on

- [ADR-0003](0003-sanctum-bearer-token-authentication.md) — which user object
  `Auth::user()` actually returns on these routes.
- [ADR-0011](0011-role-column-and-admin-middleware.md) — the other authorization
  mechanism in the system.

## Drift

- **Two of the three ownership checks are broken.** `update()` (line 98) and
  `destroy()` (line 133) call `error('', '...', 403)` with no `$this`. There is no
  global `error()` function, so PHP throws
  `Error: Call to undefined function error()` and returns HTTP 500. The authorization
  decision is still *correct* — the `if` condition is evaluated before the call — but
  the response is a 500 with a stack trace instead of a 403. In a deployment with
  `APP_DEBUG=true`, that 500 body includes an absolute filesystem path and a source
  excerpt.
- **Ownership is also unenforced at the storage layer.** The
  `records.user_id` foreign key was dropped in its own migration
  ([ADR-0001](0001-record-as-the-core-aggregate.md)), so `user_id` can be null or
  dangling. A record with `user_id = null` fails all three checks for everyone,
  including the backoffice user who created it.
- **`Api\RecordController::index()` has no ownership check and does not need one** — it
  correctly scopes through `$request->user()->records()`. This is worth stating
  explicitly: it is the one listing endpoint in the API that is right by construction,
  and it is right because it starts from the user rather than from `Record::`.
