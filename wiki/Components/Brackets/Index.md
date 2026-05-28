# Bracket Components

Four components that together render tournament bracket views. Currently only the single-elimination layout is fully implemented; double-elimination and round-robin show placeholder alerts.

**Location:** `resources/js/components/brackets/`

---

## Overview

```
BracketContainer
  └── SingleEliminationBracket
        └── MatchCard (one per match)

MatchDetailDialog  (opened by match click, manages schedule + result submission)
```

---

## Data Types

The bracket components import types from `@/pages/tournaments` (i.e. `resources/js/pages/tournaments/index.tsx`).

### BracketStructure

```ts
export type BracketStructure = {
    id: string;
    type: BracketType;      // 0 = SingleElimination, 1 = DoubleElimination, 2 = PoolFinal, 3 = Repechage
    status: BracketStatus;  // 0 = Pending, 1 = Active, 2 = Completed
    sections: {
        Main?: BracketRound[];
        Repechage?: BracketRound[];
        Bronze?: BracketRound[];
        Final?: BracketRound[];
    };
};
```

### BracketRound

```ts
export type BracketRound = {
    round_number: number;
    round_name: string;    // e.g. "Round of 16", "Quarter-Final", "Final"
    matches: BracketMatch[];
};
```

### BracketMatch

```ts
export type BracketMatch = {
    id: string;
    match_number: number;
    white: MatchCompetitor | null;  // null = TBD (bye or not yet determined)
    blue: MatchCompetitor | null;
    winner_id: string | null;       // player_id of the winner, or null
    status: MatchStatus;            // 0 = Pending, 1 = Active, 2 = Completed
    scheduled_at: string | null;    // ISO datetime string
    notes: string | null;
};
```

### MatchCompetitor

```ts
export type MatchCompetitor = {
    registration_id: string;
    player_id: string;
    name: string;
    seed: number | null;
    score: number | null;
    penalties: number | null;
    result: CompetitorResult;       // 0=Pending, 1=Win, 2=Loss, 3=WalkoverWin, 4=Disqualified
    profile_photo_url: string | null;
};
```

### MatchStatus enum

```ts
export enum MatchStatus {
    Pending = 0,
    Active = 1,
    Completed = 2,
}
```

> Note: `match-card.tsx` and `match-detail-dialog.tsx` import `Match`, `TournamentType`, and `MatchStatus` with UPPER_CASE member names (`MatchStatus.COMPLETED`, `MatchStatus.PENDING`, `MatchStatus.ONGOING`). These are **not yet exported** from `pages/tournaments/index.tsx` — the bracket components reference a future export shape. The `matches/index.tsx` page uses the current `BracketMatch` type and the PascalCase `MatchStatus.Completed` enum directly.

---

## BracketContainer

**File:** `resources/js/components/brackets/bracket-container.tsx`

### Props

```ts
interface BracketContainerProps {
    bracket: BracketStructure;
    onMatchClick?: (matchId: string) => void;
    viewOnly?: boolean;  // default false
}
```

### Behaviour

Switches on `bracket.type` (a `TournamentType` / `BracketType` numeric value):

| Type | Rendered as |
|------|------------|
| `SINGLE_ELIMINATION` (0) | `SingleEliminationBracket` with `bracket.brackets.main` rounds |
| `DOUBLE_ELIMINATION` (1) | Info alert + `SingleEliminationBracket` (full double-elim coming soon) |
| `ROUND_ROBIN` (other) | Info alert only |
| Unknown | `<div>Unknown bracket type</div>` |

Passes `onMatchClick` and `viewOnly` down to `SingleEliminationBracket`.

---

## SingleEliminationBracket

**File:** `resources/js/components/brackets/single-elimination-bracket.tsx`

### Props

```ts
interface SingleEliminationBracketProps {
    rounds: BracketRound[];
    onMatchClick?: (matchId: string) => void;
    viewOnly?: boolean;  // default false
}
```

### Layout

Renders rounds as horizontal columns in a flex row (`gap-6`, `p-4`). Each column is `min-w-[240px]`.

**Round header:** sticky, card-style (`bg-card/95 backdrop-blur-sm`) showing `round.round_name` and `round.round_number`.

**Match spacing:** gaps between matches increase exponentially across rounds using:
```
gap = roundIndex > 0 ? `${Math.pow(2, roundIndex) * 0.75}rem` : '0.75rem'
```
This mimics the visual alignment of bracket lines where later rounds have fewer, more spread-out matches.

Each match is a `MatchCard` that calls `onMatchClick?.(match.id)` when clicked.

---

## MatchCard

**File:** `resources/js/components/brackets/match-card.tsx`

### Props

```ts
interface MatchCardProps {
    match: Match;          // BracketMatch-compatible type
    onClick?: () => void;
    viewOnly?: boolean;    // default false
}
```

### Displayed information

The card uses `Card` + `CardContent` with `p-3`.

**Clickability:** the card is clickable (hover shadow + border-primary) only when `!viewOnly && match.status !== MatchStatus.COMPLETED`.

**Each competitor row** (player1 / player2) shows:
- `Avatar` with profile photo or initials fallback
- Seed badge (`#N`) if `player1.seed` is set
- Truncated player name
- Score (`font-bold`) if non-null
- `Trophy` icon if this player is the winner

Winner row gets `bg-primary/10 border-primary font-semibold`.

**BYE / VS divider:** shows `"BYE"` when `match.has_bye` is true, otherwise `"VS"`.

**Match info footer** (shown when match is scheduled or not Pending):
- Scheduled date formatted as `"MMM d, h:mm a"` using `date-fns/format`
- Status `Badge`: `secondary` for Completed, `default` for Ongoing, `outline` for Pending

---

## MatchDetailDialog

**File:** `resources/js/components/brackets/match-detail-dialog.tsx`

### Props

```ts
interface MatchDetailDialogProps {
    match: Match | null;
    tournamentId: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}
```

Returns `null` when `match` is null.

### Sections

**Match info panel:** shows both competitors as `Avatar` + name + seed badge cards with a "vs" divider.

**Schedule section:** a `datetime-local` input pre-populated from `match.scheduled_at`. On change, immediately fires an Inertia `router.patch` to `tournaments.matches.update-schedule` without closing the dialog.

**Record result form** (shown only when both `match.player1` and `match.player2` are set):
- An `Alert` (destructive) is shown when no schedule is set yet, blocking submission.
- **Winner buttons:** two `Button` toggles, one per player, highlight with `variant="default"` when selected.
- **Score inputs:** two numeric inputs (`min="0"`) for player1 and player2 scores.
- Submits via `resultForm.patch` to `tournaments.matches.update-result`. The submit button is disabled until a schedule exists, a winner is selected, and the form is not processing.

### Inertia routes used

| Action | Method | Route name |
|--------|--------|-----------|
| Save schedule | `PATCH` | `tournaments.matches.update-schedule` |
| Save result | `PATCH` | `tournaments.matches.update-result` |

---

## Usage

The bracket components are used from `resources/js/pages/tournaments/matches/index.tsx`:

```tsx
import BracketContainer from '@/components/brackets/bracket-container';
import MatchDetailDialog from '@/components/brackets/match-detail-dialog';

// In the page:
const [selectedMatchId, setSelectedMatchId] = useState<string | null>(null);
const [dialogOpen, setDialogOpen] = useState(false);

<BracketContainer
    bracket={bracketStructure}
    onMatchClick={(matchId) => {
        setSelectedMatchId(matchId);
        setDialogOpen(true);
    }}
    viewOnly={false}
/>

<MatchDetailDialog
    match={selectedMatch}
    tournamentId={tournament.id}
    open={dialogOpen}
    onOpenChange={setDialogOpen}
/>
```

The page receives `bracketStructure: BracketStructure | null` from the Inertia controller. When null, a "no bracket generated" alert is shown and a "Generate Bracket" button triggers bracket generation.
