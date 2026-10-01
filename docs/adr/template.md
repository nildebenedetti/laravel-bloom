# NNNN. Title of the decision

- **Status:** Proposed | Accepted | Superseded by ADR-NNNN | Deprecated
- **Date:** YYYY-MM-DD
- **Area:** Domain | Architecture | Data | API | Security | Frontend | Infrastructure | Testing
- **Affects:** `path/to/file.php`, `routes/api.php`, …

## Context

The situation that forced a choice. What constraints were in play — team size,
existing code, the shape of the product, what had already been built? Write this so
that someone who joins in two years understands *why there was a question at all*.

State the forces and constraints, not the conclusion.

## Decision

We decided to **do the thing**.

Be specific enough that a reader can check whether the code complies. Name the
mechanisms, not just the intent: "Sanctum personal access tokens" is a decision,
"token-based auth" is a slogan.

## Consequences

### Positive

What this buys us.

### Negative

What this costs us. Every decision has a cost; an ADR that lists only benefits is
propaganda, not a record. Be honest about the trade-off you accepted.

### Neutral / follow-on

Downstream decisions forced by this one. Link them with relative Markdown links
(`[ADR-0003](0003-sanctum-bearer-token-authentication.md)`).

## Drift

Optional, but include it whenever an ADR is written retrospectively or the
implementation has since diverged.

State plainly what the code does today versus what this record says it should do, with
file references. Examples of what belongs here:

- A symbol, route or config key that this decision assumed but that was never created.
- A validation rule that contradicts a column width.
- A mechanism that was chosen and then silently bypassed at every call site.

Drift is not a bug list — the bug list lives in [technical-debt](../technical-debt.md).
Drift is specifically the gap between the decision and its implementation.
