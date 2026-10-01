# Architecture Decision Records

Decision records for **Bloom**, the journaling & milestone tracking platform.

An ADR captures *why* a decision was made, not *what* the code looks like. The code is
the source of truth for the "what"; these files are the record of the "why", and the
record of where reality has since diverged from the intent.

## How to read these

Every ADR in this folder is **retrospective**. They were written *after* the fact by
reading the code, the migrations and the git history — they are reconstructions of
decisions that were made while building, not proposals for the future.

Because of that, each record carries a **Drift** section that states plainly where the
implementation no longer matches the decision it claims to follow. If you are here to
understand the system, read the Drift section: it is usually the most useful part.

| Status | Meaning |
| --- | --- |
| **Accepted** | The decision is implemented. Any drift is listed under *Drift*. |
| **Superseded by ADR-NNNN** | A later record replaced this one. |
| **Deprecated** | No longer applies, kept for historical context. |

## Index

| # | Title | Area | Status |
| --- | --- | --- | --- |
| [0001](0001-record-as-the-core-aggregate.md) | Record as the core aggregate | Domain | Accepted |
| [0002](0002-decoupled-architecture-with-a-separate-spa.md) | Decoupled architecture with a separate SPA | Architecture | Accepted |
| [0003](0003-sanctum-bearer-token-authentication.md) | Sanctum bearer token authentication | Security | Accepted |
| [0004](0004-blade-backoffice-for-administration.md) | Blade backoffice for administration | Frontend | Accepted |
| [0005](0005-laravel-breeze-as-the-web-auth-baseline.md) | Laravel Breeze as the web auth baseline | Security | Accepted |
| [0006](0006-normalized-taxonomies-with-nullable-foreign-keys.md) | Normalized taxonomies with nullable foreign keys | Data | Accepted |
| [0007](0007-many-to-many-emotions-via-an-explicit-pivot.md) | Many-to-many emotions via an explicit pivot | Data | Accepted |
| [0008](0008-visibility-as-a-cast-enum.md) | Visibility modelled as a cast enum | Data | Accepted |
| [0009](0009-json-api-shaped-api-resources.md) | JSON:API shaped API resources | API | Accepted |
| [0010](0010-ownership-checks-in-controllers-instead-of-policies.md) | Ownership checks in controllers, not policies | Security | Accepted |
| [0011](0011-role-column-and-admin-middleware.md) | Role column and `IsAdmin` middleware | Security | Accepted |
| [0012](0012-mysql-as-the-primary-datastore.md) | MySQL as the primary datastore | Infrastructure | Accepted |
| [0013](0013-form-requests-as-the-validation-boundary.md) | Form Requests as the validation boundary | API | Accepted |
| [0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md) | Hand-built analytics endpoints for the dashboard | API | Accepted |
| [0015](0015-local-disk-for-image-uploads.md) | Local disk for image uploads | Infrastructure | Accepted |
| [0016](0016-migrations-as-the-schema-source-of-truth.md) | Migrations as the schema source of truth | Data | Accepted |
| [0017](0017-phpunit-feature-tests-from-the-breeze-baseline.md) | PHPUnit feature tests from the Breeze baseline | Testing | Accepted |

## The decisions that shaped this system most

Three decisions explain most of Bloom's shape:

1. **[0002] Decoupled architecture.** The React SPA is a *separate repository* on port
   5173. This repo owns the API and the Blade backoffice. Every consequence in
   [0003](0003-sanctum-bearer-token-authentication.md) (bearer tokens over cookies),
   [0009](0009-json-api-shaped-api-resources.md) (a real serialization boundary) and
   [0014](0014-hand-built-analytics-endpoints-for-the-dashboard.md) (chart data
   pre-shaped for Recharts) follows from it.

2. **[0001] Record as the core aggregate.** `Record` is the only table with real
   behaviour. `Category`, `Tier` and `Emotion` are thin taxonomies. This is why the
   analytics endpoint can join three of them in a single query and why the pivot table
   is the only non-trivial relation in the schema.

3. **[0010] Authorization in controllers.** Ownership is checked with an inline
   `Auth::user()->id !== $record->user_id` comparison rather than a policy. It is
   repeated verbatim in three methods. See [0010](0010-ownership-checks-in-controllers-instead-of-policies.md)
   for why, and [technical-debt](../technical-debt.md) for what it cost.

## Process

**Writing a new ADR.** Copy [`template.md`](template.md) to
`NNNN-short-kebab-case-title.md` using the next free number. The number is never reused,
even if the record is later superseded — a superseded decision still happened.

**When to write one.** Write an ADR when a decision is expensive to reverse (a data
model, an auth strategy, a serialization contract), when a reasonable person would
argue for a different answer, or when a constraint is not visible in the code.

**When not to.** Naming conventions, folder layout, formatting, and anything a linter
or framework convention already decides.

**Superseding.** Do not edit an accepted ADR's Decision section. Add a new ADR, set the
old one to `Superseded by ADR-NNNN`, and link both ways. The history of a wrong turn is
usually more useful than a tidied-up record of it.

## Related documentation

- [Architecture overview](../architecture/overview.md) — how the pieces fit together
- [Data model](../architecture/data-model.md) — tables, columns, relations
- [API reference](../api/reference.md) — every endpoint
- [Setup & contribution guide](../guides/setup-and-contribution.md) — running it locally
- [Technical debt](../technical-debt.md) — verified list of known defects
