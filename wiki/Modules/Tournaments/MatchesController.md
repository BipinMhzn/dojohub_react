# MatchesController

**File:** `app/Modules/Tournaments/Controllers/MatchesController.php`  
**Extends:** `BaseWebController`  
**Middleware:** `auth`, `verified`

Manages bracket generation, match result recording, and match scheduling for a category. Delegates all bracket logic to `BracketService`.

---

## Constructor

```php
public function __construct(
    private readonly TournamentInterface $tournamentRepository,
    private readonly TournamentCategoryInterface $categoryRepository,
    private readonly BracketInterface $bracketRepository,
    private readonly BracketServiceInterface $bracketService,
)
```

---

## GET `/manage/tournaments/{tournament}/categories/{category}/matches` — `index`

**Inertia component:** `tournaments/matches/index`

**Controller steps:**
1. Load tournament, category, and bracket (null if not yet generated)
2. If bracket exists: call `$bracketService->getBracketStructure($bracket)` to get the nested rounds/matches data

**Props:**
```ts
{
  tournament: Tournament,
  category: TournamentCategory,
  bracket: TournamentBracket | null,
  bracketStructure: BracketStructure | null,
}
```

`BracketStructure` is a custom data shape returned by `BracketService` (rounds as nested arrays).

---

## POST `/manage/tournaments/{tournament}/categories/{category}/matches/generate` — `generate`

**Request:** `BracketGenerateRequest`

**Controller steps:**
1. Load category
2. Check if bracket already exists → if so: `back()->with('error', 'Bracket already generated for this category.')`
3. Create bracket stub: `$bracketRepository->createBracket($categoryId, $request->validated())`
4. Generate matches: `$bracketService->generateBracket($category, $bracket)`
5. On exception: `back()->with('error', $e->getMessage())`
6. On success: redirect to matches index

**Redirect (success):** `to_route('tournaments.categories.matches.index', [$tournamentId, $categoryId])->with('success', 'Bracket generated successfully.')`

**BracketGenerateRequest fields:**

| Field | Rules |
|-------|-------|
| `type` | required, Enum(BracketTypeEnum) |
| `seeding_method` | required, Enum(SeedingMethodEnum) |

---

## PATCH `/manage/tournaments/{tournament}/categories/{category}/matches/{match}/result` — `updateResult`

**Request:** `MatchResultRequest`

Records the result of a match.

**Controller steps:**
1. Call `$bracketService->recordMatchResult($matchId, $inputs['winner_position'], white_score?, white_penalties?, blue_score?, blue_penalties?, notes?)`
2. On exception: `back()->with('error', $e->getMessage())`
3. On success: redirect to matches index

**MatchResultRequest fields:**

| Field | Rules |
|-------|-------|
| `winner_position` | required, Enum(CompetitorPositionEnum) — `0`=White, `1`=Blue |
| `white_score` | nullable, integer |
| `white_penalties` | nullable, integer |
| `blue_score` | nullable, integer |
| `blue_penalties` | nullable, integer |
| `notes` | nullable, string |

---

## PATCH `/manage/tournaments/{tournament}/categories/{category}/matches/{match}/schedule` — `updateSchedule`

**Request:** `MatchScheduleRequest`

Assigns a scheduled time to a match.

**Controller steps:**
1. Call `$bracketService->scheduleMatch($matchId, $inputs['scheduled_at'])`
2. On exception: `back()->with('error', $e->getMessage())`
3. On success: redirect to matches index

**MatchScheduleRequest fields:**

| Field | Rules |
|-------|-------|
| `scheduled_at` | required, datetime |

---

## BracketService — key methods

### `generateBracket(TournamentCategory $category, TournamentBracket $bracket): Collection`

1. Calls `seedRegistrations()` — requires ≥2 active registrations
2. Wraps match creation in `DB::transaction()`
3. Generates single-elimination bracket (power-of-2 slots with byes)
4. Updates bracket: `competitor_count`, `round_count`, `status=Active`, `generated_at`

### `recordMatchResult(string $matchId, ...)`

Updates winner, scores, penalties, notes, and `completed_at`. Advances winner to next match slot. All in a transaction.

### `scheduleMatch(string $matchId, string $scheduledAt)`

Sets `scheduled_at` on the match.

---

## Related

- [ResultsController](ResultsController.md)
- [RegistrationsController](RegistrationsController.md)
- [System/TournamentBracket](../../System/TournamentBracket.md)
- [System/TournamentMatch](../../System/TournamentMatch.md)
