# 0011. Role column and `IsAdmin` middleware

- **Status:** Accepted
- **Date:** 2026-09-18
- **Area:** Security
- **Affects:** `app/Models/User.php`, `app/Http/Middleware/IsAdmin.php`, `routes/web.php`, `config/cors.php`

## Context

The backoffice manages users, roles, and the three controlled vocabularies
([ADR-0004](0004-blade-backoffice-for-administration.md)). It must not be reachable by
an ordinary registered user. Bloom's roles are deliberately tiny — `admin` and
`user` — with no teams, no per-resource permissions, and no invitation model.

Laravel's authorization toolkit offers several levels here: Gates, a permissions
package (Spatie, etc.), a `role` column with a custom check, or a `can` method on the
model. The smallest option that answers the only question Bloom asks — "is this request
from an administrator?" — is a middleware.

## Decision

We added a **nullable-by-default string `role` column** to `users` and a **single
middleware** that reads it.

**The column** — `2026_09_18_134648_add_role_column_to_users_table.php`:

```php
$table->string('role')->default('user')->after('email');
```

**The predicate** lives on the model, not the middleware:

```php
// app/Models/User.php
public function isAdmin(): bool { return $this->role === 'admin'; }
```

**The middleware** — `app/Http/Middleware/IsAdmin.php` — checks it and aborts with a
403 and a human-readable message:

```php
public function handle(Request $request, Closure $next): Response
{
    if (auth()->check() && auth()->user()->isAdmin()) {
        return $next($request);
    }

    abort(403, 'Access denied. Only Admins can access this area.');
}
```

**It is applied as a route-group middleware**, composed with the two gates that already
existed (`routes/web.php:31-33`):

```php
Route::middleware('auth', 'verified', IsAdmin::class)
        ->prefix('admin')
        ->group( function () { /* ... */ });
```

It is referenced by **fully-qualified class name**, not by an alias, because
`bootstrap/app.php`'s `withMiddleware()` is empty.

**Roles are editable by admins only** — the only place `role` is written is
`Admin\UserController::store()` and `update()`, both of which go through
`StoreUserRequest` / `UpdateUserRequest`, which validate `'role' => 'required|in:admin,user'`.

## Consequences

### Positive

- One line in the model and one class in the middleware covers the whole requirement.
- `abort(403)` produces Laravel's standard error response, which `shouldRenderJsonWhen`
  turns into JSON on `/api/*` and which renders as a proper 403 page on the web side.
- The `'user'` default means existing rows and new self-registered users are
  non-admin without any extra work, so the admin surface is closed by default.
- Composing `auth, verified, IsAdmin` in one group means a new admin route inherits
  all three gates by being added to the group.

### Negative

- **`isAdmin()` is a bare string comparison.** A typo in the role value, a rename of
  the `admin` literal in a seeder, or a case difference (`Admin`) all silently produce
  `false`. There is no `UserRole` enum, unlike `RecordVisibility`
  ([ADR-0008](0008-visibility-as-a-cast-enum.md)), so the two halves of the app model
  authorization differently.
- **The role column is a free-text string, not constrained.** Nothing at the database
  level prevents `role = 'superuser'`. The `in:admin,user` rule lives only in two Form
  Requests, so any other write path is unchecked.
- **`role` is in `User::$fillable`.** See *Drift* — this is the single most dangerous
  line in the model.
- **There is no `admin` capability on the API.** Bearer tokens authenticate but never
  authorize by role: no API route uses `IsAdmin`, and
  `Api\RecordController::show()` will 403 an administrator trying to read a record they
  do not own ([ADR-0010](0010-ownership-checks-in-controllers-instead-of-policies.md)).
  The API and the backoffice have genuinely different privilege models.
- The middleware re-checks `auth()->check()` even though `auth` is already in the
  group — harmless, but it means the middleware is not independently correct if reused.

### Neutral / follow-on

- [ADR-0010](0010-ownership-checks-in-controllers-instead-of-policies.md) — the
  resource-level rule.
- [ADR-0013](0013-form-requests-as-the-validation-boundary.md) — where `role` is
  validated.

## Drift

- **`role` is in `User::$fillable`, which makes it mass-assignable.** `User` declares
  *two different* fillable lists that disagree:

  ```php
  #[Fillable(['name', 'email', 'password'])]     // line 14 — attribute, does NOT include role
  #[Hidden(['password', 'remember_token'])]
  class User extends Authenticatable
  {
      protected $fillable = ['name', 'email', 'role', 'password'];   // line 21 — includes role
  }
  ```

  The `$fillable` property shadows the attribute, so `role` is mass-assignable and the
  attribute is dead code. Today no code path exploits this — `ProfileUpdateRequest` has
  no `role` rule, so `validated()` never returns it, and the admin path that *does*
  write `role` is admin-gated. But it is one `$request->all()` away from privilege
  escalation, and it is the reason `technical-debt.md` ranks it high.
- **`MustVerifyEmail` is not implemented, so the `verified` gate in the same middleware
  chain is inert** ([ADR-0005](0005-laravel-breeze-as-the-web-auth-baseline.md)).
- **`IsAdmin` is referenced by FQCN while everything else in that group is a string
  alias**, because `bootstrap/app.php` registers no aliases. This works, but it means
  the middleware cannot be renamed or swapped without a search across `routes/`.
- **`IsAdmin.php` imports itself** — `use App\Http\Middleware\IsAdmin;` on line 5 of
  the class that defines it. Harmless, and a clear sign the file was copy-pasted from
  a template.
- **The admin `UserController` has no self-deletion guard.** An admin can delete their
  own account via `DELETE /admin/users/{user}`, and the next request through the
  `IsAdmin` chain will 403. There is no "last admin" protection, so a two-admin system
  can lock itself out.
