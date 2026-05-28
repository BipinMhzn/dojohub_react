# Tournaments Module

**Directory:** `app/Modules/Tournaments/` + `app/Modules/TournamentCategories/`  
**Routes prefix:** `/manage/tournaments`  
**Route names prefix:** `tournaments`

The core competition module. Manages tournament CRUD, per-tournament categories, registrations, bracket generation, match results, and final standings. Split across two modules: `Tournaments` owns match/bracket/registration logic; `TournamentCategories` owns category CRUD.

---

## Controllers

| Controller | Module | Route prefix | Inertia pages |
|-----------|--------|-------------|---------------|
| `TournamentsController` | Tournaments | `/manage/tournaments` | `tournaments/index`, `tournaments/show`, `tournaments/create` |
| `TournamentCategoriesController` | TournamentCategories | `/manage/tournaments/{t}/categories` | `tournaments/categories/index`, `tournaments/categories/create`, `tournaments/categories/edit` |
| `RegistrationsController` | Tournaments | `/manage/tournaments/{t}/categories/{c}/registrations` | `tournaments/registrations/index` |
| `MatchesController` | Tournaments | `/manage/tournaments/{t}/categories/{c}/matches` | `tournaments/matches/index` |
| `ResultsController` | Tournaments | `/manage/tournaments/{t}/categories/{c}/results` | `tournaments/results/index` |

---

## Routes (full list)

```
# Tournaments
GET    /manage/tournaments                    → TournamentsController@index
GET    /manage/tournaments/create             → TournamentsController@create
POST   /manage/tournaments                    → TournamentsController@store
GET    /manage/tournaments/{tournament}       → TournamentsController@show
GET    /manage/tournaments/{tournament}/edit  → TournamentsController@edit  *redirect to show
POST   /manage/tournaments/{tournament}       → TournamentsController@update
DELETE /manage/tournaments/{tournament}       → TournamentsController@destroy

# Categories (TournamentCategories module)
GET    /manage/tournaments/{t}/categories              → TournamentCategoriesController@index
GET    /manage/tournaments/{t}/categories/create       → TournamentCategoriesController@create
POST   /manage/tournaments/{t}/categories              → TournamentCategoriesController@store
GET    /manage/tournaments/{t}/categories/{c}/edit     → TournamentCategoriesController@edit
PUT    /manage/tournaments/{t}/categories/{c}          → TournamentCategoriesController@update
DELETE /manage/tournaments/{t}/categories/{c}          → TournamentCategoriesController@destroy

# Registrations
GET    /manage/tournaments/{t}/categories/{c}/registrations         → RegistrationsController@index
POST   /manage/tournaments/{t}/categories/{c}/registrations         → RegistrationsController@store
DELETE /manage/tournaments/{t}/categories/{c}/registrations/{r}     → RegistrationsController@destroy

# Matches / Brackets
GET    /manage/tournaments/{t}/categories/{c}/matches               → MatchesController@index
POST   /manage/tournaments/{t}/categories/{c}/matches/generate      → MatchesController@generate
PATCH  /manage/tournaments/{t}/categories/{c}/matches/{m}/result    → MatchesController@updateResult
PATCH  /manage/tournaments/{t}/categories/{c}/matches/{m}/schedule  → MatchesController@updateSchedule

# Results
GET    /manage/tournaments/{t}/categories/{c}/results               → ResultsController@index
```

All routes: `auth` + `verified` middleware.

---

## Database tables

| Table | Model | Module |
|-------|-------|--------|
| `tournaments` | `Tournament` | Tournaments |
| `tournament_categories` | `TournamentCategory` | TournamentCategories |
| `tournament_registrations` | `TournamentRegistration` | Tournaments |
| `tournament_brackets` | `TournamentBracket` | Tournaments |
| `tournament_matches` | `TournamentMatch` | Tournaments |
| `tournament_match_competitors` | `TournamentMatchCompetitor` | Tournaments |
| `tournament_results` | `TournamentResult` | Tournaments |
| `tournament_rule_sets` | `TournamentRuleSet` | TournamentRuleSets |

---

## Service bindings (Tournaments module)

```php
$this->app->bind(TournamentInterface::class, TournamentRepository::class);
$this->app->bind(RegistrationInterface::class, RegistrationRepository::class);
$this->app->bind(BracketInterface::class, BracketRepository::class);
$this->app->bind(MatchInterface::class, MatchRepository::class);
$this->app->bind(BracketServiceInterface::class, BracketService::class);
```

---

## Module files

| File | Purpose |
|------|---------|
| `Controllers/TournamentsController.php` | Tournament CRUD |
| `Controllers/TournamentCategoriesController.php` | Category CRUD (in TournamentCategories module) |
| `Controllers/RegistrationsController.php` | Player registration to categories |
| `Controllers/MatchesController.php` | Bracket generation + match result/schedule |
| `Controllers/ResultsController.php` | View final standings |
| `Repositories/TournamentRepository.php` | Tournament data access |
| `Repositories/RegistrationRepository.php` | Registration data access |
| `Repositories/BracketRepository.php` | Bracket data access |
| `Repositories/MatchRepository.php` | Match data access |
| `Services/BracketService.php` | Bracket generation + result recording logic |
| `Exceptions/PlayerAlreadyRegisteredException.php` | Thrown on duplicate registration |
| `Filters/TournamentsFilter.php` | `keyword` → `name` filter |

---

## Related

- [TournamentsController](TournamentsController.md)
- [TournamentCategoriesController](TournamentCategoriesController.md)
- [RegistrationsController](RegistrationsController.md)
- [MatchesController](MatchesController.md)
- [ResultsController](ResultsController.md)
- [TournamentRepository](TournamentRepository.md)
- [System/Tournament](../../System/Tournament.md)
