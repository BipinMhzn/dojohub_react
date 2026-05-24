# Tournament Lifecycle — Implementation Plan

> **Scope**: Category-driven tournament management for DojoHub.
>
> **Core design decisions (confirmed):**
> - Category status is the single source of truth. Tournament status is computed from it.
> - All category status transitions are **manual** — managers drive the lifecycle; no date-based automation.
> - Bracket generation is **always manual** — the manager selects the type and triggers it.
> - `tournament_registrations.player_weight` is **removed** — `tournament_weigh_ins` is the authoritative weight source.
> - A cancelled tournament is a **full stop** — no further actions may be taken on any of its categories, registrations, or matches.

---

## Table of Contents

1. [Schema Changes](#1-schema-changes)
2. [Enum Rework](#2-enum-rework)
3. [TournamentStatusResolver Design](#3-tournamentstatusresolver-design)
4. [Category Lifecycle & Transitions](#4-category-lifecycle--transitions)
5. [Registration Workflow](#5-registration-workflow)
6. [Weigh-In Workflow](#6-weigh-in-workflow)
7. [Bracket Generation Workflow](#7-bracket-generation-workflow)
8. [Match Execution Workflow](#8-match-execution-workflow)
9. [State Transition Rules](#9-state-transition-rules)
10. [Domain Events](#10-domain-events)
11. [Implementation Phases](#11-implementation-phases)

---

## 1. Schema Changes

### 1.1 Already implemented (migrations `2026_01_01_*`) ✅

- `tournament_categories`: 6 scheduling timestamp columns (`registration_open_at`, `registration_close_at`, `start_at`, `end_at`, `weigh_in_start_at`, `weigh_in_end_at`), `decimal(5,2)` weights, 5 CHECK constraints, soft-delete-safe unique.
- `tournament_matches`: `mat_id` FK → `venue_mats`, `pool_id`, `match_outcome`, `is_golden_score`, `golden_score_duration_seconds`, `winning_competitor_id` → `tournament_match_competitors` (no `winner_id → players`). Self-ref FKs in pass-2 migration.
- `tournament_weigh_ins`, `tournament_registration_fees`, `tournament_registration_payments`, `tournament_registration_coaches`, `tournament_pools`, `tournament_pool_competitors`, `tournament_match_referees`, `tournament_match_events` — all present.
- Soft-delete safe unique constraints (`deleted_at` included) on all junction tables.

### 1.2 Outstanding schema change — remove `player_weight` from registrations

Write migration `remove_player_weight_from_tournament_registrations_table.php`:

```php
Schema::table('tournament_registrations', function (Blueprint $table) {
    $table->dropColumn('player_weight');
});
```

**Why**: `tournament_weigh_ins` is the authoritative weight source with full history. A snapshot at registration time is redundant and creates two sources of truth. The official weight for bracket eligibility is the most recent weigh-in record.

**Impact**: Remove `player_weight` from:
- `TournamentRegistration` system model fillable / casts
- `TournamentRegistrationCreateRequest` / `TournamentRegistrationUpdateRequest`
- Frontend registration form and TypeScript `TournamentRegistration` type
- Registration repository snapshot logic

### 1.3 Scheduling timestamps — display only

The six scheduling datetime columns on `tournament_categories` are **informational** — they are displayed to managers and participants as the intended schedule but they do **not** trigger automatic status transitions. Transitions remain 100% manual.

**Validation rules** (Laravel `CategoryCreateRequest` / `CategoryUpdateRequest`):

```php
'registration_open_at'  => 'nullable|date',
'registration_close_at' => 'nullable|date|after:registration_open_at',
'weigh_in_start_at'     => 'nullable|date|after_or_equal:registration_close_at',
'weigh_in_end_at'       => 'nullable|date|after:weigh_in_start_at',
'start_at'              => 'nullable|date|after_or_equal:registration_close_at',
'end_at'                => 'nullable|date|after:start_at',
```

---

## 2. Enum Rework

### 2.1 `CategoryStatusEnum` — expand to full lifecycle

**Location**: `app/System/Tournament/Foundation/Enums/CategoryStatusEnum.php`

Current (4 states) → New (8 states). All transitions are **manager-triggered**:

| Value | Case | Meaning | Trigger |
|-------|------|---------|---------|
| 0 | `Draft` | Category created; registrations not yet open | Auto on creation |
| 1 | `RegistrationOpen` | Registrations being accepted | **Manager — manual** |
| 2 | `RegistrationClosed` | No new registrations; weigh-in / seeding may begin | **Manager — manual** |
| 3 | `WeighIn` | Official weigh-in phase active | **Manager — manual** (optional phase) |
| 4 | `BracketGenerated` | Bracket created, matches ready | System — on bracket generation success |
| 5 | `InProgress` | Matches being contested | System — on first match activated |
| 6 | `Completed` | All matches done, results finalised | System — on last match completing |
| 7 | `Cancelled` | Category cancelled (terminal) | **Manager — manual** |

> **Data migration required**: Old values 0=Pending, 1=Seeding, 2=Active, 3=Completed need mapping before deploying.
> Suggested: `Pending→Draft(0)`, `Seeding→RegistrationClosed(2)`, `Active→InProgress(5)`, `Completed→Completed(6)`.

### 2.2 `TournamentStatusEnum` — keep 4 states

No new states. Values stay as-is:

| Value | Case | Resolver Rule |
|-------|------|---------------|
| 0 | `Scheduled` | All non-cancelled categories in Draft/RegistrationOpen/RegistrationClosed/WeighIn; or no categories |
| 1 | `InProgress` | At least one category is BracketGenerated, InProgress |
| 2 | `Completed` | All non-cancelled categories are Completed |
| 3 | `Cancelled` | Manager-set; **resolver never overrides this** |

### 2.3 `RegistrationStatusEnum` — no changes

`Registered=0`, `Confirmed=1`, `Withdrawn=2` — unchanged.

---

## 3. TournamentStatusResolver Design

**Location**: `app/Modules/Tournaments/Services/TournamentStatusResolver.php`

**Interface**: `app/Modules/Tournaments/Interfaces/TournamentStatusResolverInterface.php`

### 3.1 Priority Resolution Rules

Categories with `Cancelled=7` status are excluded from all computations.

```
resolve(Tournament $tournament): TournamentStatusEnum
```

| Priority | Condition | Resolved Status |
|----------|-----------|-----------------|
| 1 | Tournament is `Cancelled=3` | `Cancelled` — resolver never overrides manual cancellation |
| 2 | No non-cancelled categories exist | `Scheduled` |
| 3 | Any category is `InProgress=5` or `BracketGenerated=4` | `InProgress` |
| 4 | All non-cancelled categories are `Completed=6` | `Completed` |
| 5 | All non-cancelled categories are in pre-bracket states (Draft, RegistrationOpen, RegistrationClosed, WeighIn) | `Scheduled` |

### 3.2 Mixed-State Handling

| Example | Resolved Status |
|---------|-----------------|
| Cat A: InProgress, Cat B: Draft | InProgress |
| Cat A: Completed, Cat B: InProgress | InProgress |
| Cat A: Completed, Cat B: BracketGenerated | InProgress |
| Cat A: Completed, Cat B: RegistrationOpen | Scheduled |
| Cat A: Completed, Cat B: Completed | Completed |
| Cat A: Cancelled, Cat B: Completed | Completed |
| All: Cancelled | Scheduled |

### 3.3 Automatic Update Hook

Fires on every `CategoryStatusChanged` event:

```php
class SyncTournamentStatus
{
    public function handle(CategoryStatusChanged $event): void
    {
        $tournament = $event->category->tournament;
        if ($tournament->status === TournamentStatusEnum::Cancelled) {
            return; // never override manual cancellation
        }
        $newStatus = $this->resolver->resolve($tournament);
        if ($tournament->status !== $newStatus) {
            $old = $tournament->status;
            $tournament->update(['status' => $newStatus]);
            TournamentStatusChanged::dispatch($tournament, $old, $newStatus);
        }
    }
}
```

### 3.4 Cancellation Guard

Every repository method that mutates tournament data (categories, registrations, matches, brackets) must begin with:

```php
if ($tournament->status === TournamentStatusEnum::Cancelled) {
    throw new TournamentCancelledException('No actions may be taken on a cancelled tournament.');
}
```

This guard also applies to category-level mutations when the parent tournament is cancelled. The exception is caught in the controller and returned as a 422 with a clear UI message.

### 3.5 Tournament Cancellation Action

Cancellation is a dedicated action — not part of the regular `update` route:

```
POST /manage/tournaments/{tournament}/cancel    tournaments.cancel
```

Steps:
1. Confirm tournament is not already Cancelled.
2. Transition all non-Completed, non-Cancelled categories → `Cancelled` (no notifications cascade needed for this batch).
3. Set `tournament.status = Cancelled` directly (bypasses resolver).
4. Fire `TournamentCancelled` event.
5. Return redirect with warning banner: "This tournament has been cancelled. No further changes are possible."

---

## 4. Category Lifecycle & Transitions

### 4.1 Lifecycle Diagram

All transitions labelled [M] are **manual** (manager-triggered). Transitions labelled [S] are **system-triggered** (from BracketService or MatchRepository).

```
                         [M]
Draft ──────────────────────────────► RegistrationOpen
                                              │
                                         [M] │
                                              ▼
                                    RegistrationClosed
                                         │       │
                                    [M]  │       │ [M] (skip WeighIn)
                                         ▼       │
                                       WeighIn   │
                                         │       │
                                    [M]  │       │
                                         ▼       │
                              (manager triggers) │
                               BracketService ◄──┘
                                         │
                                    [S] bracket generated
                                         ▼
                                  BracketGenerated
                                         │
                                    [S] first match activated
                                         ▼
                                     InProgress
                                         │
                                    [S] all matches completed
                                         ▼
                                      Completed  ← terminal

Any state (except Completed) ──[M]──► Cancelled  ← terminal
```

> WeighIn is an **optional** phase. The manager can skip from `RegistrationClosed → BracketGenerated` by triggering bracket generation directly.

### 4.2 `CategoryStatusTransitionService`

**Location**: `app/Modules/Tournaments/Services/CategoryStatusTransitionService.php`

```php
public function transitionTo(TournamentCategory $category, CategoryStatusEnum $target, User $actor): void
```

- Checks parent tournament is not Cancelled first.
- Asserts transition is in the allowed table (§9.1).
- Wraps in a DB transaction.
- Fires `CategoryStatusChanged` on success.

---

## 5. Registration Workflow

### 5.1 Allowed Registration Window

Registrations may be created **only when** `category.status === RegistrationOpen`.

`RegistrationsController@store` checks this first and returns 422 if the status is wrong. The parent tournament must not be Cancelled.

### 5.2 Eligibility Validation

Order of checks (fail-fast). All run in `RegistrationRepository::createRegistration()`:

| # | Check | Error |
|---|-------|-------|
| 1 | Tournament is not Cancelled | "No actions may be taken on a cancelled tournament" |
| 2 | Category status is `RegistrationOpen` | "Registration is not open for this category" |
| 3 | Player gender matches `category.gender` | "Player gender does not match this category" |
| 4 | Player age (from `players.dob`) is within `[min_age, max_age]` | "Player age {n} is outside the {min}–{max} range" |
| 5 | Player `players.weight` is within `[min_weight, max_weight]` | "Player weight {w}kg is outside the {min}–{max}kg range" |
| 6 | No active registration for `(tournament_category_id, player_id)` | "Player is already registered in this category" |

> **Weight check at registration (§5.2 check 5)** uses `players.weight` as a preliminary eligibility gate only. The **official** weight is recorded by weigh-in (§6). No weight is snapshotted on the registration row — `player_weight` is removed from that table.

### 5.3 Duplicate Prevention

DB unique index `(tournament_category_id, player_id, deleted_at)` catches races. Repository catches `UniqueConstraintViolationException` and converts to 422.

**Re-registration after withdrawal**: If a player was withdrawn and the category re-opens, restore the registration (set `status = Registered`, clear `deleted_at`) rather than creating a duplicate row.

### 5.4 Registration Closing Behaviour

When manager transitions `RegistrationOpen → RegistrationClosed`:
- All registrations with `status = Registered` are bulk-updated to `status = Confirmed`.
- `Withdrawn` registrations remain unchanged.
- Done atomically inside `CategoryStatusTransitionService::transitionTo()`.

### 5.5 Withdrawal Behaviour

A manager may withdraw a registration (soft-delete + set `status = Withdrawn`) when `category.status` is `RegistrationOpen`, `RegistrationClosed`, or `WeighIn`.

Once `category.status` is `BracketGenerated` or `InProgress`, withdrawal requires explicit manager confirmation and triggers a warning: "Withdrawing a competitor after bracket generation may leave unfilled match slots."

---

## 6. Weigh-In Workflow

### 6.1 When Allowed

Weigh-in records may be created when `category.status` is `RegistrationClosed`, `WeighIn`, or `BracketGenerated`. Returns 422 for any other status (including Cancelled).

### 6.2 `made_weight` Computation

Computed at record time and persisted:

```php
$madeWeight = $weight >= $category->min_weight && $weight <= $category->max_weight;
```

Uses the `tournament_categories` bounds at weigh-in time (which managers may have adjusted from enum defaults).

### 6.3 Official Weight

The "official" weight for a registration is the **most recent** `tournament_weigh_ins` record by `weighed_at`. This is the sole source of truth — there is no `player_weight` snapshot on the registration row.

Frontend: show the latest weigh-in prominently, with a collapsible history table below.

### 6.4 Failed Weigh-In Handling

If `made_weight = false`:
- Registration is NOT automatically withdrawn.
- A manager must explicitly withdraw the registration if the athlete is disqualified.
- Additional weigh-in attempts can be recorded (all history is kept).

---

## 7. Bracket Generation Workflow

### 7.1 Bracket Generation is Always Manual

Bracket generation is **always triggered by the manager** regardless of category status. The manager:
1. Navigates to the category's matches/bracket view.
2. Selects bracket type (`SingleElimination`, `DoubleElimination`, `PoolFinal`, `Repechage`).
3. Selects seeding method (`Random`, `ByRank`, `Manual`).
4. Clicks "Generate Bracket".

The system does **not** auto-generate brackets based on status or dates.

### 7.2 Pre-conditions

`BracketService::generateBracket()` checks:

| Condition | Error |
|-----------|-------|
| Tournament is not Cancelled | "No actions may be taken on a cancelled tournament" |
| At least 2 confirmed (non-withdrawn) registrations exist | "Cannot generate bracket with fewer than 2 competitors" |
| No existing Active or Completed bracket for this category | "Bracket already generated — regenerate instead" |

> There is no category status pre-condition. A manager can generate a bracket from `RegistrationClosed`, `WeighIn`, or any pre-bracket state.

### 7.3 Generation Steps

1. Collect registrations with `status = Confirmed` (not Withdrawn).
2. Seed per `bracket.seeding_method`:
   - `Random` — shuffle collection
   - `ByRank` — sort by `registration.created_at` ascending (first to register = top seed)
   - `Manual` — order by pre-assigned `seed` values set by the manager before generation
3. Create `TournamentBracket` with `status = Pending`.
4. Run the algorithm for the selected type (`generateSingleElimination`, `generateRepechage`, etc.).
5. Update bracket: `status = Active`, `competitor_count`, `round_count`, `generated_at = now()`.
6. Transition `category.status → BracketGenerated` via `CategoryStatusTransitionService`.
7. Fire `BracketGenerated` event.

### 7.4 Bye Handling

When competitor count is not a power of two, top-seeded athletes receive byes. Bye matches are immediately `Completed` with `result = WalkoverWin`. The bye slot has `registration_id = null`.

### 7.5 Regeneration

Allowed when `category.status === BracketGenerated` (not yet InProgress). Manager must confirm intent.

On regeneration:
1. Soft-delete all `TournamentMatch` and `TournamentMatchCompetitor` records under the bracket.
2. Soft-delete the `TournamentBracket`.
3. Transition `category.status → RegistrationClosed` (or whichever pre-bracket state applies).
4. Run generation fresh from step 7.3.

---

## 8. Match Execution Workflow

### 8.1 First Match Activation

When a match transitions to `Active`:
- If `category.status === BracketGenerated`, transition `category.status → InProgress`.

### 8.2 Result Recording

`MatchRepository::recordResult()` steps:

1. Confirm tournament is not Cancelled.
2. Validate `winning_competitor_id` belongs to this match's competitors.
3. Validate both competitor slots are filled (not a bye).
4. Set winner: `result = Win`, update `score`, `penalties`, `match_outcome`, `is_golden_score`, `golden_score_duration_seconds`.
5. Set loser: `result = Loss`, update `score`, `penalties`.
6. Update match: `status = Completed`, `completed_at = now()`, `winning_competitor_id`.
7. If `winner_next_match_id` set: create `TournamentMatchCompetitor` in the next match at `winner_next_position`.
8. If `loser_next_match_id` set: create `TournamentMatchCompetitor` in the repechage match at `loser_next_position`.
9. Fire `MatchCompleted` event.

### 8.3 Category Completion Detection

After every result is recorded:

```php
$allDone = $category->brackets()
    ->with('matches')
    ->get()
    ->flatMap->matches
    ->every(fn($m) => $m->status === MatchStatusEnum::Completed);

if ($allDone) {
    $transitionService->transitionTo($category, CategoryStatusEnum::Completed, $actor);
}
```

### 8.4 Result Generation

On `CategoryCompleted` event, `AutoGenerateResults` listener populates `tournament_results`:
- Position 1 (Gold): winner of the GoldFinal match
- Position 2 (Silver): loser of the GoldFinal match
- Position 3 (Bronze): winner(s) of Bronze match(es)
- Position 5 (5th): losers of Repechage semifinal (if tracked)

Managers may override positions manually after auto-generation.

---

## 9. State Transition Rules

### 9.1 Allowed Category Transitions

All manager-triggered transitions are **manual**. System-triggered transitions fire automatically from service logic.

| From | To | Trigger |
|------|----|---------|
| `Draft` | `RegistrationOpen` | Manager |
| `Draft` | `Cancelled` | Manager |
| `RegistrationOpen` | `RegistrationClosed` | Manager |
| `RegistrationOpen` | `Cancelled` | Manager |
| `RegistrationClosed` | `WeighIn` | Manager (optional — may skip to bracket generation) |
| `RegistrationClosed` | `BracketGenerated` | System (bracket generation success) |
| `RegistrationClosed` | `Cancelled` | Manager |
| `WeighIn` | `RegistrationClosed` | Manager (step back if needed) |
| `WeighIn` | `BracketGenerated` | System (bracket generation success) |
| `WeighIn` | `Cancelled` | Manager |
| `BracketGenerated` | `InProgress` | System (first match activated) |
| `BracketGenerated` | `RegistrationClosed` | Manager (triggers regeneration reset) |
| `BracketGenerated` | `Cancelled` | Manager |
| `InProgress` | `Completed` | System (all matches done) |
| `InProgress` | `Cancelled` | Manager (with confirmation) |
| `Completed` | *(none)* | Terminal |
| `Cancelled` | *(none)* | Terminal |

### 9.2 Forbidden Transitions

Any transition not listed above is rejected by `CategoryStatusTransitionService` with 422:
`"Cannot transition category from {from} to {to}."`

### 9.3 Rollback Behaviour

No automatic rollback. Manual rollback paths:
- `BracketGenerated → RegistrationClosed`: manager triggers regeneration, which soft-deletes bracket + matches then re-runs.
- All other rollbacks require cancellation.

Transitions are wrapped in a DB transaction — if any step throws, the status is not changed:

```php
DB::transaction(function () use ($category, $target, $actor) {
    $this->assertTournamentNotCancelled($category->tournament);
    $this->assertValidTransition($category->status, $target);
    $from = $category->status;
    $category->update(['status' => $target]);
    CategoryStatusChanged::dispatch($category, $from, $target);
});
```

---

## 10. Domain Events

**Location**: `app/Modules/Tournaments/Events/`

### Event Catalogue

| Event | Payload | Fired By |
|-------|---------|---------|
| `CategoryStatusChanged` | `$category`, `$from`, `$to` | `CategoryStatusTransitionService` |
| `RegistrationCreated` | `$registration` | `RegistrationRepository` |
| `RegistrationWithdrawn` | `$registration` | `RegistrationRepository` |
| `WeighInRecorded` | `$weighIn`, `$madeWeight: bool` | `WeighInRepository` |
| `BracketGenerated` | `$bracket`, `$category` | `BracketService` |
| `MatchCompleted` | `$match`, `$winner: TournamentMatchCompetitor` | `MatchRepository` |
| `CategoryCompleted` | `$category` | `CategoryStatusTransitionService` |
| `TournamentStatusChanged` | `$tournament`, `$from`, `$to` | `SyncTournamentStatus` listener |
| `TournamentCancelled` | `$tournament` | `TournamentRepository` |

### Listener Map

| Event | Listeners |
|-------|-----------|
| `CategoryStatusChanged` | `SyncTournamentStatus`; `BulkConfirmRegistrations` (when → RegistrationClosed) |
| `MatchCompleted` | `CheckCategoryCompletion` |
| `CategoryCompleted` | `AutoGenerateResults` |

---

## 11. Implementation Phases

### Phase L1 — Remove `player_weight` from registrations

1. Write and run migration `remove_player_weight_from_tournament_registrations_table.php`.
2. Remove from `TournamentRegistration` system model fillable.
3. Remove from `TournamentRegistrationCreateRequest` / update request.
4. Remove snapshot logic from `RegistrationRepository::createRegistration()`.
5. Remove from frontend registration form and TypeScript `TournamentRegistration` type.
6. Update `wiki/Database.md` registration table entry.

### Phase L2 — Enum Updates

1. Update `CategoryStatusEnum.php` — 8 cases (Draft=0 through Cancelled=7).
2. Keep `TournamentStatusEnum.php` at 4 cases (Scheduled=0, InProgress=1, Completed=2, Cancelled=3) — values unchanged.
3. Update `wiki/Enums.md`, `wiki/Database.md`, `wiki/TournamentsModule.md`.
4. `grep -rn CategoryStatusEnum app/ resources/` — fix all references.
5. `grep -rn TournamentStatusEnum app/ resources/` — confirm no references expect old value mapping.
6. Write data migration if existing rows carry old status integers (Seeding=1 → RegistrationClosed=2, Active=2 → InProgress=5, Completed=3 → Completed=6).
7. `vendor/bin/pint --dirty`.

### Phase L3 — Services & Events

1. Create event classes (§10) in `app/Modules/Tournaments/Events/`.
2. `TournamentStatusResolver` service + interface + binding.
3. `CategoryStatusTransitionService` + interface + binding.
4. `TournamentCancelledException` in `app/Modules/Tournaments/Exceptions/`.
5. `SyncTournamentStatus` listener.
6. `BulkConfirmRegistrations` listener.
7. `CheckCategoryCompletion` listener.
8. `AutoGenerateResults` listener.
9. Register all in Tournaments `ServiceProvider`.

### Phase L4 — Repository & Controller Updates

1. `TournamentRepository`: add `cancelTournament()` with bulk category cancellation.
2. `CategoryRepository`: all status changes go through `CategoryStatusTransitionService`.
3. `RegistrationRepository`:
   - Add cancellation guard at start of every mutating method.
   - `createRegistration()`: eligibility checks §5.2, fire `RegistrationCreated`.
   - `withdrawRegistration()`: status guard, fire `RegistrationWithdrawn`.
   - `reactivateRegistration()`: for re-registration after withdrawal.
4. `MatchRepository::recordResult()`: steps per §8.2, fire `MatchCompleted`.
5. `WeighInRepository` (new): `recordWeighIn()` with `made_weight` computation, cancellation guard.

### Phase L5 — Routes & Controllers

New routes:

```
POST   /manage/tournaments/{t}/cancel                                   tournaments.cancel
POST   /manage/tournaments/{t}/categories/{c}/transition                categories.transition
POST   /manage/tournaments/{t}/categories/{c}/registrations/{r}/withdraw registrations.withdraw
GET    /manage/tournaments/{t}/categories/{c}/weigh-ins                  weigh-ins.index
POST   /manage/tournaments/{t}/categories/{c}/weigh-ins                  weigh-ins.store
```

`CategoryTransitionRequest` validates `target` against allowed transitions for the current status.

### Phase L6 — Frontend Updates

1. Update `CategoryStatusEnum` TS values (8 states).
2. Keep `TournamentStatusEnum` TS values (4 states, unchanged integers).
3. Add scheduling fields to `categories/{create,edit}.tsx` (date/time pickers, all optional).
4. Add status transition action buttons to category list — show only valid next states.
5. Add cancel confirmation dialog to tournament edit page.
6. Add weigh-in panel on registration detail (history table + record form).
7. Update `status-badge.tsx` for all 8 category states.
8. Remove `player_weight` from registration form.
9. Update TypeScript types.

### Phase L7 — Tests

- `CategoryStatusTransitionServiceTest`: all valid transitions, all invalid transitions, cancellation guard, DB rollback.
- `TournamentStatusResolverTest`: all mixed-state combinations from §3.2, cancellation lock.
- `TournamentCancellationTest`: all mutation paths blocked after cancellation.
- `RegistrationWorkflowTest`: eligibility failures, weight check, duplicate prevention, re-registration.
- `WeighInWorkflowTest`: `made_weight` computation, multiple attempts, failed weigh-in.
- `BracketGenerationWorkflowTest`: manual trigger, pre-condition checks, bye handling, regeneration.
- `MatchExecutionWorkflowTest`: result recording, competitor propagation, category completion trigger.
- `DomainEventsTest`: each event fires in the correct scenarios.
