# Tournament Lifecycle — Implementation Plan

> **Implementation status (as of this revision):** **All phases L1–L6 are complete.** The full lifecycle
> is wired: 7-state `CategoryStatusEnum`, `TournamentStatusResolver`, `CategoryStatusTransitionService`
> (with bulk-confirm on close, **regeneration reset** on `BracketGenerated → RegistrationClosed`, and
> **result generation** on `→ Completed`), tournament cancellation, registration eligibility/withdrawal,
> bracket generation, match result recording, and the **weigh-in workflow**
> (`TournamentWeighIn` model, `WeighInRepository`, `WeighInsController`, routes, and the
> `tournaments/weigh-ins` page with a derived weigh-in-phase badge). **Bracket generation now also
> requires every confirmed competitor's *latest* weigh-in to have `made_weight = true`** (§7.2), not
> merely a weigh-in record. **Phase L6 (automated tests) is done** —
> `CategoryStatusTransitionServiceTest`, `TournamentStatusResolverTest`, `TournamentCancellationTest`,
> `RegistrationWorkflowTest` (now incl. eligibility/duplicate/club checks), `WeighInWorkflowTest`,
> `BracketGenerationWorkflowTest`, and `MatchExecutionWorkflowTest` all exist and pass, plus a
> `WeighInTest` HTTP feature test.
>
> Note: match-result recording lives in `BracketService::recordMatchResult()` (not `MatchRepository`),
> and result generation keys off the terminal match (no `winner_next_match_id`) rather than the
> `GoldFinal` section, so 2-competitor brackets resolve correctly.

> **Project state:** This is a **fresh project**. The database has no production data, so the
> reset path is always `php artisan migrate:fresh --seed`. That single fact drives the whole plan:
>
> - **No data migrations, no `ALTER` migrations.** Schema and enum changes are made by **editing the
>   original create-migrations and enum files in place**. There is nothing to preserve, so we never
>   write a `remove_*` / `change_*` migration or map old integer values to new ones.
> - Pick the **final** enum integer values directly. The only "migration" concern is keeping the
>   frontend TypeScript enums in sync with the backend.
>
> **Core design decisions (this revision):**
> - **Category status is the single source of truth.** Tournament status is *computed* from its
>   categories by a resolver and is never set directly except for manual cancellation.
> - **All forward lifecycle transitions a manager cares about are manual.** Scheduling timestamps are
>   informational only — they never auto-advance status.
> - **Bracket generation is always manual** — the manager picks the type + seeding and triggers it.
> - **Weigh-in is a *phase*, not a status** (design change — see §2.1). It overlaps
>   `RegistrationClosed` and `BracketGenerated`, so it cannot be a single linear state.
> - **`tournament_registrations.player_weight` is removed** — `tournament_weigh_ins` is the
>   authoritative weight source.
> - **A cancelled tournament is a full stop** — no further action on any of its categories,
>   registrations, brackets, or matches.
> - **Orchestration is synchronous, inside services** — *not* a custom domain-event bus (design
>   change — see §10). This matches the existing codebase, which uses Eloquent model-event
>   subscribers (e.g. `VenueEventSubscriber`) and service-level orchestration, never bespoke event
>   classes.

---

## Table of Contents

1. [Schema Changes (edit-in-place)](#1-schema-changes-edit-in-place)
2. [Enum Rework (edit-in-place)](#2-enum-rework-edit-in-place)
3. [TournamentStatusResolver Design](#3-tournamentstatusresolver-design)
4. [Category Lifecycle & Transitions](#4-category-lifecycle--transitions)
5. [Registration Workflow](#5-registration-workflow)
6. [Weigh-In Workflow](#6-weigh-in-workflow)
7. [Bracket Generation Workflow](#7-bracket-generation-workflow)
8. [Match Execution Workflow](#8-match-execution-workflow)
9. [State Transition Rules](#9-state-transition-rules)
10. [Orchestration & Side Effects](#10-orchestration--side-effects)
11. [Implementation Phases](#11-implementation-phases)

---

## 1. Schema Changes (edit-in-place)

The full tournament schema already exists in migrations `2026_01_01_000012`–`000027`
(`tournaments`, `tournament_categories`, `tournament_registrations`, `..._weigh_ins`,
`..._brackets`, `..._pools`, `..._matches`, `..._match_competitors`, `..._results`, etc.). Because
the DB is greenfield, the changes below are made **directly in those create-migrations** and applied
with `migrate:fresh`.

### 1.1 Remove `player_weight` from `tournament_registrations`

Edit `2026_01_01_000014_create_tournament_registrations_table.php` and **delete** this line:

```php
$table->decimal('player_weight', 5, 2);   // ← remove
```

**Why:** `tournament_weigh_ins` already records weight with full history and a `made_weight` flag.
A snapshot at registration time is a second source of truth for the same fact. The official weight
for bracket eligibility is the **most recent weigh-in record**; the preliminary eligibility gate at
registration uses the live `players.weight` column (§5.2).

**Code impact** (remove `player_weight` everywhere it appears):
- `TournamentRegistration` system model — `$fillable` / `casts()`.
- `TournamentRegistrationCreateRequest` / `...UpdateRequest`.
- `RegistrationRepository` snapshot logic.
- Frontend registration form + the `TournamentRegistration` TS type.

### 1.2 Update the `status` column comment on `tournament_categories`

No structural change — the column stays `tinyInteger unsigned default 0`. Just update the inline
comment in `2026_01_01_000013_create_tournament_categories_table.php` to match the new enum (§2.1):

```php
$table->tinyInteger('status')->unsigned()->default(0);
// 0=Draft, 1=RegistrationOpen, 2=RegistrationClosed, 3=BracketGenerated, 4=InProgress, 5=Completed, 6=Cancelled
```

### 1.3 Scheduling timestamps — informational only

The six `dateTime` columns on `tournament_categories` (`registration_open_at`,
`registration_close_at`, `weigh_in_start_at`, `weigh_in_end_at`, `start_at`, `end_at`) stay as-is.
They are **displayed** as the intended schedule and drive the weigh-in *phase* indicator (§6), but
they never trigger an automatic status transition.

**Validation** (`CategoryCreateRequest` / `CategoryUpdateRequest`):

```php
'registration_open_at'  => 'nullable|date',
'registration_close_at' => 'nullable|date|after:registration_open_at',
'weigh_in_start_at'     => 'nullable|date|after_or_equal:registration_close_at',
'weigh_in_end_at'       => 'nullable|date|after:weigh_in_start_at',
'start_at'              => 'nullable|date|after_or_equal:registration_close_at',
'end_at'                => 'nullable|date|after:start_at',
```

### 1.4 No other schema changes

The existing columns already support the whole lifecycle:
`tournament_matches.winning_competitor_id`, `winner_next_match_id`, `loser_next_match_id` (added in
the pass-2 migration `000027`), `bracket_section`, `match_outcome`, `is_golden_score` — all present.

> ⚠️ **Existing bug to fix during implementation:** `BracketService::advanceByeWinner()` references
> `$byeMatch->winner_id`, but the matches table has **`winning_competitor_id`** (a
> `tournament_match_competitors` FK), not `winner_id`. Update that reference when touching match flow.

---

## 2. Enum Rework (edit-in-place)

### 2.1 `CategoryStatusEnum` — refined 7-state lifecycle

**Location:** `app/System/Tournament/Foundation/Enums/CategoryStatusEnum.php`
**Current (4 states):** `Pending=0, Seeding=1, Active=2, Completed=3` → **replace in place** with:

| Value | Case | Meaning | Set by |
|-------|------|---------|--------|
| 0 | `Draft` | Created; config editable; registrations not open | Auto on creation |
| 1 | `RegistrationOpen` | Accepting registrations | **Manager** |
| 2 | `RegistrationClosed` | No new registrations; weigh-in + seeding happen here | **Manager** |
| 3 | `BracketGenerated` | Bracket + matches created; ready to compete | System — on bracket generation |
| 4 | `InProgress` | Matches being contested | System — on first match result |
| 5 | `Completed` | All matches done, results finalised | System — on last match completing |
| 6 | `Cancelled` | Terminal | **Manager** |

**Design change from the prior draft — `WeighIn` is no longer a status.** The prior draft made
`WeighIn` a distinct, "optional and skippable" state, yet also allowed weigh-in records during
`RegistrationClosed` *and* `BracketGenerated`. A condition that legitimately spans three states is a
**phase**, not a status — modelling it as a linear state forced awkward `WeighIn ⇄ RegistrationClosed`
back-and-forth transitions for no gain. Weigh-in is now derived from the
`weigh_in_start_at … weigh_in_end_at` window plus the presence of weigh-in records (§6), and is shown
as a badge independent of status.

`BracketGenerated` and `InProgress` stay distinct: the gap between them is the **regeneration
window** (§7.5) — a real manager capability worth its own state.

> Greenfield: no data migration. Replace the enum cases, run `migrate:fresh --seed`.

### 2.2 `TournamentStatusEnum` — unchanged

Stays 4 states: `Scheduled=0, InProgress=1, Completed=2, Cancelled=3`. Always computed by the
resolver (§3), except `Cancelled`, which is set manually and never overridden.

### 2.3 `RegistrationStatusEnum` — unchanged

`Registered=0, Confirmed=1, Withdrawn=2`.

### 2.4 Frontend enum sync (the only "migration" that matters here)

- `resources/js/pages/tournaments/index.tsx` — the exported `enum CategoryStatus` must mirror the 7
  backend values exactly.
- `resources/js/pages/tournaments/partials/category-columns.tsx` — the `categoryStatusLabel` and
  `categoryStatusVariant` `Record<number, string>` maps must cover all 7 values.
- `resources/js/pages/tournaments/registrations/index.tsx` — `canRegister` must compare against
  `CategoryStatus.RegistrationOpen` (was `.Pending`).

---

## 3. TournamentStatusResolver Design

**Service:** `app/Modules/Tournaments/Services/TournamentStatusResolver.php`
**Interface:** `app/Modules/Tournaments/Interfaces/TournamentStatusResolverInterface.php` (bound in the
Tournaments `ServiceProvider`).

```php
public function resolve(Tournament $tournament): TournamentStatusEnum
```

Categories with `Cancelled` status are excluded from every computation.

### 3.1 Resolution rules (first match wins)

| # | Condition | Resolved status |
|---|-----------|-----------------|
| 1 | Tournament is already `Cancelled` | `Cancelled` — resolver never overrides manual cancellation |
| 2 | No non-cancelled categories exist | `Scheduled` |
| 3 | Any non-cancelled category is `BracketGenerated` or `InProgress` | `InProgress` |
| 4 | All non-cancelled categories are `Completed` | `Completed` |
| 5 | Otherwise (all in `Draft` / `RegistrationOpen` / `RegistrationClosed`) | `Scheduled` |

### 3.2 Mixed-state examples

| Categories | Resolved |
|------------|----------|
| InProgress + Draft | InProgress |
| Completed + InProgress | InProgress |
| Completed + BracketGenerated | InProgress |
| Completed + RegistrationOpen | Scheduled |
| Completed + Completed | Completed |
| Cancelled + Completed | Completed |
| All Cancelled | Scheduled |

### 3.3 When it runs

The resolver is invoked **synchronously** at the end of `CategoryStatusTransitionService::transitionTo()`
(§4.2) — not via an event listener. After a successful category transition the service calls the
resolver and, if the computed tournament status differs and the tournament is not `Cancelled`,
updates `tournaments.status` in the same DB transaction.

```php
private function syncTournamentStatus(Tournament $tournament): void
{
    if ($tournament->status === TournamentStatusEnum::Cancelled) {
        return; // never override a manual cancellation
    }
    $resolved = $this->resolver->resolve($tournament);
    if ($tournament->status !== $resolved) {
        $tournament->update(['status' => $resolved]);
    }
}
```

### 3.4 Cancellation guard

Every repository/service method that mutates tournament data (categories, registrations, brackets,
matches, weigh-ins) begins with a guard:

```php
if ($tournament->status === TournamentStatusEnum::Cancelled) {
    throw new TournamentCancelledException('No actions may be taken on a cancelled tournament.');
}
```

`TournamentCancelledException` lives in `app/Modules/Tournaments/Exceptions/`, is caught in the
controller, and returns a 422 (Inertia: redirect back with an `error` flash).

### 3.5 Tournament cancellation action

A dedicated action, **not** part of the regular update route:

```
POST /manage/tournaments/{tournament}/cancel    →  tournaments.cancel
```

`TournamentRepository::cancelTournament()` (inside one DB transaction):
1. Reject if already `Cancelled`.
2. Set every non-`Completed`, non-`Cancelled` category → `Cancelled` (direct update — bypasses the
   transition table; cancellation is allowed from any non-terminal state anyway).
3. Set `tournaments.status = Cancelled` directly (bypasses the resolver).
4. Redirect back with a warning banner: *"This tournament has been cancelled. No further changes are
   possible."*

---

## 4. Category Lifecycle & Transitions

### 4.1 Lifecycle diagram

`[M]` = manual (manager). `[S]` = system (fired from a service).

```
            [M]                    [M]                         [S] generate
  Draft ──────────► RegistrationOpen ──────────► RegistrationClosed ──────────► BracketGenerated
                                                       ▲      │                       │   ▲
                                                 [M] regen    │                       │   │ [M] regen reset
                                                  reset ──────┘                       │   │
                                                                          [S] first match result
                                                                                      ▼   │
                                                                                  InProgress
                                                                                      │
                                                                          [S] all matches completed
                                                                                      ▼
                                                                                  Completed  (terminal)

  Any non-terminal state ──[M]──► Cancelled  (terminal)
```

Weigh-in is **not** on this diagram — it is a phase active during `RegistrationClosed` and
`BracketGenerated` (§6).

### 4.2 `CategoryStatusTransitionService`

**Location:** `app/Modules/Tournaments/Services/CategoryStatusTransitionService.php`

```php
public function transitionTo(TournamentCategory $category, CategoryStatusEnum $target, User $actor): void
```

Inside one DB transaction:
1. Assert the parent tournament is not `Cancelled` (§3.4).
2. Assert `from → target` is allowed (§9.1); else throw → 422.
3. Run any transition side effect (§10) — e.g. bulk-confirm on `→ RegistrationClosed`.
4. `$category->update(['status' => $target])`.
5. `syncTournamentStatus($category->tournament)` (§3.3).

If any step throws, the transaction rolls back and the status is unchanged.

---

## 5. Registration Workflow

### 5.1 Registration window

Registrations may be created **only when** `category.status === RegistrationOpen`.
`RegistrationsController@store` (and `RegistrationRepository::createRegistration()`) enforce this and
the cancellation guard first.

### 5.2 Eligibility validation (fail-fast, in `createRegistration()`)

| # | Check | Error |
|---|-------|-------|
| 1 | Tournament not `Cancelled` | "No actions may be taken on a cancelled tournament" |
| 2 | `category.status === RegistrationOpen` | "Registration is not open for this category" |
| 3 | `players.gender` matches `category.gender` | "Player gender does not match this category" |
| 4 | Age from `players.dob` within `[min_age, max_age]` | "Player age {n} is outside the {min}–{max} range" |
| 5 | `players.weight` within `[min_weight, max_weight]` | "Player weight {w}kg is outside the {min}–{max}kg range" |
| 6 | No active registration for `(tournament_category_id, player_id)` | "Player is already registered in this category" |

> Check 5 uses the live `players.weight` as a **preliminary** gate only. The official, binding weight
> is the weigh-in (§6). Nothing is snapshotted onto the registration row.

### 5.3 Duplicate prevention

The unique index `tr_category_player_unique (tournament_category_id, player_id, deleted_at)` catches
races. The repository catches `UniqueConstraintViolationException` → 422 (reuse
`PlayerAlreadyRegisteredException`, which already exists).

**Re-registration after withdrawal:** if a player was withdrawn and registration re-opens, restore
the existing row (`status = Registered`, clear `deleted_at`) rather than inserting a duplicate.

### 5.4 Closing behaviour

On the manager transition `RegistrationOpen → RegistrationClosed`, the transition service
side effect (§10) bulk-updates every `Registered` registration → `Confirmed`. `Withdrawn` rows are
left untouched. Done atomically inside the same transaction as the status change.

### 5.5 Withdrawal behaviour

A manager may withdraw a registration (soft-delete + `status = Withdrawn`) while the category is
`RegistrationOpen` or `RegistrationClosed`.

Once the category is `BracketGenerated` or `InProgress`, withdrawal requires explicit confirmation
and warns: *"Withdrawing a competitor after bracket generation may leave unfilled match slots."*

---

## 6. Weigh-In Workflow

### 6.1 When allowed

Weigh-in records may be created when `category.status` is `RegistrationClosed` or `BracketGenerated`
(and the tournament is not cancelled). Any other status → 422.

The **weigh-in phase** shown in the UI is derived, independent of status:
`now() ∈ [weigh_in_start_at, weigh_in_end_at]` *or* at least one weigh-in record exists for the
category. There is no `WeighIn` status (§2.1).

### 6.2 `made_weight` (computed + persisted at record time)

```php
$madeWeight = $weight >= $category->min_weight && $weight <= $category->max_weight;
```

Uses the `tournament_categories` bounds at weigh-in time (the manager may have adjusted them from the
enum defaults).

### 6.3 Official weight

The official weight for a registration is the **most recent** `tournament_weigh_ins` row by
`weighed_at`. Sole source of truth — no `player_weight` snapshot. Frontend shows the latest weigh-in
prominently with a collapsible history table.

### 6.4 Failed weigh-in

If `made_weight = false`: the registration is **not** auto-withdrawn. A manager decides whether to
withdraw. Additional attempts can be recorded; all history is kept.

---

## 7. Bracket Generation Workflow

### 7.1 Always manual

The manager opens the category's bracket view, picks a **type** (`SingleElimination`,
`DoubleElimination`, `PoolFinal`, `Repechage`) and **seeding method** (`Random`, `Manual`, `ByRank`),
then clicks **Generate Bracket**. The system never auto-generates.

### 7.2 Pre-conditions (`BracketService::generateBracket()`)

| Condition | Error |
|-----------|-------|
| Tournament not `Cancelled` | "No actions may be taken on a cancelled tournament" |
| Category is `RegistrationClosed` or `BracketGenerated` (regen) | "Close registration before generating a bracket" |
| Every `Confirmed` registration has a weigh-in record | "Weigh-in is incomplete: {n} competitor(s) still need to be weighed in" |
| Every `Confirmed` registration's **latest** weigh-in has `made_weight = true` | "Weigh-in is incomplete: {n} competitor(s) have not made weight. Withdraw them or record a passing weigh-in" |
| ≥ 2 `Confirmed` (non-withdrawn) registrations | "Cannot generate a bracket with fewer than 2 competitors" |
| No existing `Active`/`Completed` bracket (unless regenerating) | "Bracket already generated — regenerate instead" |

### 7.3 Generation steps

1. Collect registrations with `status = Confirmed`.
2. Seed by `seeding_method`:
   - `Random` — shuffle.
   - `ByRank` — order by `registration.created_at` asc (first to register = top seed).
   - `Manual` — order by the `seed` values the manager set before generation.
3. Create `TournamentBracket` (`status = Pending`).
4. Run the type algorithm (`generateSingleElimination`, …), creating matches + `bracket_section`
   + `winner_next_match_id` / `loser_next_match_id` links.
5. Update bracket: `status = Active`, `competitor_count`, `round_count`, `generated_at = now()`.
6. Transition `category → BracketGenerated` via `CategoryStatusTransitionService`.

### 7.4 Bye handling

When the competitor count is not a power of two, top seeds get byes. A bye match is created already
`Completed` with the present competitor's `result = WalkoverWin` and the empty slot left unfilled;
the winner is advanced to `winner_next_match_id`. (Fix the `winner_id` → `winning_competitor_id`
reference noted in §1.4 while doing this.)

### 7.5 Regeneration

Allowed only while `category.status === BracketGenerated` (never once `InProgress`). Manager confirms,
then in one transaction:
1. Soft-delete all `TournamentMatchCompetitor`, then `TournamentMatch`, then the `TournamentBracket`
   (children first so FK `RESTRICT` is never tripped — see soft-delete note in CLAUDE.md).
2. Transition `category → RegistrationClosed`.
3. Re-run §7.3 fresh.

---

## 8. Match Execution Workflow

### 8.1 Entering `InProgress`

When the **first** match result is recorded in a category, if `category.status === BracketGenerated`,
`MatchRepository` transitions it `→ InProgress` via the transition service. (There is no separate
manual "activate match" step — recording the first result is the signal.)

### 8.2 Result recording (`MatchRepository::recordResult()`)

1. Cancellation guard.
2. Validate `winning_competitor_id` belongs to this match's competitors.
3. Validate both competitor slots are filled (not a bye).
4. Winner competitor: `result = Win`; set `score`, `penalties`.
5. Loser competitor: `result = Loss`; set `score`, `penalties`.
6. Match: `status = Completed`, `completed_at = now()`, `winning_competitor_id`, `match_outcome`,
   `is_golden_score`, `golden_score_duration_seconds`.
7. If `winner_next_match_id` set: upsert a `TournamentMatchCompetitor` in that match at
   `winner_next_position`.
8. If `loser_next_match_id` set: upsert a `TournamentMatchCompetitor` in the repechage match at
   `loser_next_position`.
9. Run completion detection (§8.3).

Validate `score`/`penalties` against the tournament's rule set
(`rule_set.max_waza_ari`, `rule_set.max_shido`).

### 8.3 Category completion detection

After each result, if every match under the category's brackets is `Completed`, transition
`category → Completed` via the transition service:

```php
$allDone = $category->brackets()->with('matches')->get()
    ->flatMap->matches
    ->every(fn ($m) => $m->status === MatchStatusEnum::Completed);

if ($allDone) {
    $transitionService->transitionTo($category, CategoryStatusEnum::Completed, $actor);
}
```

### 8.4 Result generation

When a category transitions `→ Completed`, the transition service populates `tournament_results`
inline (§10):
- Position 1 (Gold): winner of the `GoldFinal` match.
- Position 2 (Silver): loser of the `GoldFinal` match.
- Position 3 (Bronze): winner(s) of the `Bronze` match(es).
- Position 5 (5th): repechage semifinal losers (if tracked).

Managers may override positions afterward.

---

## 9. State Transition Rules

### 9.1 Allowed category transitions

| From | To | Trigger |
|------|----|---------|
| `Draft` | `RegistrationOpen` | Manager |
| `Draft` | `Cancelled` | Manager |
| `RegistrationOpen` | `RegistrationClosed` | Manager (bulk-confirms registrations, §5.4) |
| `RegistrationOpen` | `Cancelled` | Manager |
| `RegistrationClosed` | `RegistrationOpen` | Manager (re-open) |
| `RegistrationClosed` | `BracketGenerated` | System (bracket generation) |
| `RegistrationClosed` | `Cancelled` | Manager |
| `BracketGenerated` | `RegistrationClosed` | Manager (regeneration reset, §7.5) |
| `BracketGenerated` | `InProgress` | System (first match result) |
| `BracketGenerated` | `Cancelled` | Manager |
| `InProgress` | `Completed` | System (all matches done) |
| `InProgress` | `Cancelled` | Manager (with confirmation) |
| `Completed` | *(none)* | Terminal |
| `Cancelled` | *(none)* | Terminal |

### 9.2 Forbidden transitions

Anything not in the table is rejected by `CategoryStatusTransitionService` with 422:
`"Cannot transition category from {from} to {to}."`

### 9.3 Rollback

No automatic rollback. The only manual back-step is `BracketGenerated → RegistrationClosed`
(regeneration, which soft-deletes bracket + matches first). Everything else exits via cancellation.
All transitions are wrapped in a DB transaction (§4.2), so a throw mid-side-effect leaves status
unchanged.

---

## 10. Orchestration & Side Effects

**Design change from the prior draft.** The prior draft specified a custom domain-event bus —
9 `Event` classes (`CategoryStatusChanged`, `BracketGenerated`, `MatchCompleted`, …) plus 4
listeners. **This revision drops that.** The codebase has no precedent for bespoke domain-event
classes; its only event usage is Eloquent model-event **subscribers** for guard rails
(`VenueEventSubscriber` blocks deleting an in-use venue). A nine-class event bus would be a new
architectural layer carrying indirection that a single-app, manager-driven workflow doesn't need.

Instead, side effects run **synchronously inside the owning service**, all within the transaction
that triggered them:

| Trigger | Owning method | Side effect (inline) |
|---------|---------------|----------------------|
| `→ RegistrationClosed` | `CategoryStatusTransitionService::transitionTo()` | Bulk-confirm `Registered` → `Confirmed` (§5.4) |
| Any category transition | `CategoryStatusTransitionService::transitionTo()` | Re-resolve + update tournament status (§3.3) |
| `→ Completed` | `CategoryStatusTransitionService::transitionTo()` | Generate `tournament_results` (§8.4) |
| Bracket generated | `BracketService::generateBracket()` | Transition category `→ BracketGenerated` |
| First match result | `MatchRepository::recordResult()` | Transition category `→ InProgress` |
| Every match result | `MatchRepository::recordResult()` | Completion detection → maybe `→ Completed` (§8.3) |

**Use an Eloquent subscriber only for a guard** (consistent with `VenueEventSubscriber`): e.g. a
`TournamentEventSubscriber` on `eloquent.updating: TournamentCategory` could enforce the cancellation
guard centrally. Optional — the per-method guard in §3.4 already covers it.

If asynchronous fan-out (notifications, emails) is needed later, *that* is the right place to
introduce queued events — but it is out of scope for this lifecycle and not a prerequisite.

---

## 11. Implementation Phases

### Phase L1 — Schema + enums (greenfield, edit-in-place) ✅

1. Edit `..._create_tournament_registrations_table.php` — remove `player_weight` (§1.1).
2. Edit `..._create_tournament_categories_table.php` — update the `status` comment (§1.2).
3. Rewrite `CategoryStatusEnum.php` — 7 cases (§2.1).
4. `php artisan migrate:fresh --seed`.
5. `grep -rn "CategoryStatusEnum" app/ resources/` — fix every reference (model cast usage,
   `TournamentCategoryRepository` default `Draft`, `RegistrationsController` `RegistrationOpen`).
6. `grep -rn "player_weight" app/ resources/` — remove from model, requests, repository, TS type, form.
7. Sync frontend enums (§2.4).
8. `vendor/bin/pint --dirty`, `npm run types`.

### Phase L2 — Services & exception ✅

1. `TournamentCancelledException` in `app/Modules/Tournaments/Exceptions/`.
2. `TournamentStatusResolver` + interface + binding (§3).
3. `CategoryStatusTransitionService` + interface + binding (§4.2), including inline side effects (§10).
4. Register both in the Tournaments `ServiceProvider`.

### Phase L3 — Repository & controller wiring ✅

1. `TournamentRepository::cancelTournament()` (§3.5).
2. `TournamentCategoryRepository` — route all status changes through `CategoryStatusTransitionService`;
   default new categories to `Draft`.
3. `RegistrationRepository` — cancellation guard on every mutator; `createRegistration()` eligibility
   (§5.2) + duplicate handling (§5.3) + re-registration restore; `withdrawRegistration()`.
4. `WeighInRepository` (new) — `recordWeighIn()` with `made_weight` computation + guard (§6).
5. `MatchRepository::recordResult()` — steps §8.2, first-result → `InProgress`, completion detection;
   fix the `winner_id` → `winning_competitor_id` bug in `BracketService` (§1.4).
6. `BracketService::generateBracket()` — pre-conditions (§7.2) + category transition (§7.3.6) +
   regeneration (§7.5).

### Phase L4 — Routes & requests ✅

```
POST   /manage/tournaments/{t}/cancel                                       tournaments.cancel
POST   /manage/tournaments/{t}/categories/{c}/transition                    categories.transition
POST   /manage/tournaments/{t}/categories/{c}/registrations/{r}/withdraw    registrations.withdraw
GET    /manage/tournaments/{t}/categories/{c}/weigh-ins                     weigh-ins.index
POST   /manage/tournaments/{t}/categories/{c}/weigh-ins                     weigh-ins.store
```

`CategoryTransitionRequest` validates `target` against the allowed transitions for the current status
(§9.1).

### Phase L5 — Frontend ✅

1. Sync `CategoryStatus` TS enum + label/variant maps for 7 states (§2.4).
2. Add the (optional) scheduling date/time pickers to `categories/{create,edit}.tsx`.
3. Category list: status-transition action buttons showing only valid next states (§9.1).
4. Tournament page: cancel confirmation dialog → `tournaments.cancel`.
5. Weigh-in panel on the registration view: latest weigh-in + history table + record form;
   weigh-in-phase badge derived per §6.1.
6. Remove `player_weight` from the registration form + TS type.

### Phase L6 — Tests ✅

- `CategoryStatusTransitionServiceTest` — every valid + invalid transition, cancellation guard, DB rollback.
- `TournamentStatusResolverTest` — all §3.2 mixed-state cases + cancellation lock.
- `TournamentCancellationTest` — all mutation paths blocked after cancellation.
- `RegistrationWorkflowTest` — each eligibility failure, weight gate, duplicate prevention, re-registration restore, bulk-confirm on close.
- `WeighInWorkflowTest` — `made_weight` computation, multiple attempts, failed weigh-in, status gating.
- `BracketGenerationWorkflowTest` — manual trigger, pre-conditions, bye handling, regeneration reset.
- `MatchExecutionWorkflowTest` — result recording, competitor propagation, first-result → `InProgress`, completion → `Completed`, results generation.
