# System Layer

The `app/System/` directory contains the **canonical Eloquent model definitions** for every domain entity. These are the single source of truth for table structure, relationships, casts, and fillable fields.

---

## What lives here

| Path | Purpose |
|------|---------|
| `AppBaseModel.php` | Abstract base class for all domain models |
| `Address/Models/Address.php` | Shared address entity (used by Venue, Club, Player) |
| `BeltRank/Models/BeltRank.php` | Judo belt rank reference data |
| `Club/Models/Club.php` | Club entity |
| `ClubCoach/Models/ClubCoach.php` | Club–Coach pivot/membership |
| `ClubPlayer/Models/ClubPlayer.php` | Club–Player pivot/membership |
| `Coach/Models/Coach.php` | Coach entity |
| `Country/Models/Country.php` | Country reference data |
| `Player/Models/Player.php` | Player entity |
| `Referee/Models/Referee.php` | Referee entity |
| `Tournament/Models/Tournament.php` | Tournament entity |
| `Tournament/Models/TournamentCategory.php` | Category within a tournament |
| `Tournament/Models/TournamentBracket.php` | Bracket for a category |
| `Tournament/Models/TournamentMatch.php` | Single match within a bracket |
| `Tournament/Models/TournamentMatchCompetitor.php` | Competitor slot in a match |
| `Tournament/Models/TournamentRegistration.php` | Player registration to a category |
| `Tournament/Models/TournamentResult.php` | Final standings for a category |
| `TournamentRuleSet/Models/TournamentRuleSet.php` | Rule set (scoring, timing) for tournaments |
| `User/Models/User.php` | Authenticated user |
| `Venue/Models/Venue.php` | Venue entity |
| `VenueMat/Models/VenueMat.php` | Individual mat within a venue |
| `Foundation/Traits/FileUpload.php` | Controller trait for uploading files |
| `Foundation/Traits/AvatarGenerator.php` | Generate initials avatar URLs |

---

## Design rules

1. **Module models are aliases.** Each `app/Modules/{Module}/Models/{Entity}.php` simply re-exports or aliases the System model — all actual columns, relationships, and casts are defined in `app/System/`.
2. **Never query `App\System\...\Model` directly from a controller.** Use the module's repository interface.
3. **All domain models extend `AppBaseModel`** — which gives them ULID PKs, soft-delete, audit logging, and editor tracking. The only exception is `BeltRank` (reference data, no audit trail needed) and `User` (auth model, different base class).
4. **Enums live under `Foundation/Enums/`** within each System entity's directory.

---

## Enums reference

| Namespace | Enum | Values |
|-----------|------|--------|
| `App\System\BeltRank\Foundation\Enums` | `BeltRankKindEnum` | `Kyu=0`, `Dan=1` |
| `App\System\Player\Foundation\Enums` | `PlayerGenderEnum` | `Male=0`, `Female=1` |
| `App\System\Referee\Foundation\Enums` | `RefereeCertificationLevelEnum` | `Club=0`, `National=1`, `Continental=2`, `International=3` |
| `App\System\Referee\Foundation\Enums` | `RefereeRoleEnum` | `Center=0`, `Side=1`, `Video=2` |
| `App\System\Tournament\Foundation\Enums` | `AgeCategoryEnum` | `Preparation=0`, `SuperMini=1`, `Mini=2`, `Pupils=3`, `Juveniles=4`, `Cadets=5`, `Juniors=6`, `Under23=7`, `Seniors=8`, `Veterans=9` |
| `App\System\Tournament\Foundation\Enums` | `BracketSectionEnum` | `Main=0`, `Repechage=1`, `Bronze=2`, `GoldFinal=3` |
| `App\System\Tournament\Foundation\Enums` | `BracketStatusEnum` | `Pending=0`, `Active=1`, `Completed=2` |
| `App\System\Tournament\Foundation\Enums` | `BracketTypeEnum` | `SingleElimination=0`, `DoubleElimination=1`, `PoolFinal=2`, `Repechage=3` |
| `App\System\Tournament\Foundation\Enums` | `CategoryStatusEnum` | `Pending=0`, `Seeding=1`, `Active=2`, `Completed=3` |
| `App\System\Tournament\Foundation\Enums` | `CompetitorPositionEnum` | `White=0`, `Blue=1` |
| `App\System\Tournament\Foundation\Enums` | `CompetitorResultEnum` | `Pending=0`, `Win=1`, `Loss=2`, `WalkoverWin=3`, `Disqualified=4` |
| `App\System\Tournament\Foundation\Enums` | `MatchEventTypeEnum` | `Ippon=0`, `WazaAri=1`, `Shido=2`, `HansokuMake=3`, `HansokuMakeLight=4`, `Kinsa=5`, `Note=6` |
| `App\System\Tournament\Foundation\Enums` | `MatchOutcomeEnum` | `Ippon=0`, `WazaAri=1`, `Decision=2`, `HansokuMake=3`, `Walkover=4`, `Bye=5`, `GoldenScore=6` |
| `App\System\Tournament\Foundation\Enums` | `MatchStatusEnum` | `Pending=0`, `Active=1`, `Completed=2` |
| `App\System\Tournament\Foundation\Enums` | `PaymentMethodEnum` | `Cash=0`, `Bank=1`, `Online=2` |
| `App\System\Tournament\Foundation\Enums` | `RegistrationStatusEnum` | `Registered=0`, `Confirmed=1`, `Withdrawn=2` |
| `App\System\Tournament\Foundation\Enums` | `SeedingMethodEnum` | `Random=0`, `Manual=1`, `ByRank=2` |
| `App\System\Tournament\Foundation\Enums` | `TournamentLevelEnum` | `Local=1`, `Regional=2`, `National=3`, `International=4` |
| `App\System\Tournament\Foundation\Enums` | `TournamentStatusEnum` | `Scheduled=0`, `InProgress=1`, `Completed=2`, `Cancelled=3` |
| `App\System\Tournament\Foundation\Enums` | `WeightCategoryEnum` | `Female48=0` … `Female78Plus=13` (14 weight classes) |

---

## Related

- [AppBaseModel](AppBaseModel.md) — traits bundled into every domain model
- [Traits](Traits.md) — FileUpload, AvatarGenerator
- [database-conventions.md](../../.claude/guidelines/database-conventions.md)
- [architecture.md](../../.claude/guidelines/architecture.md)
