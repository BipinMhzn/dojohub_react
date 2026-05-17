# DojoHub Wiki

Authoritative project documentation. CLAUDE.md (project root) is the entry point and links here.

## Index

### Architecture & conventions
| Doc | Purpose |
|-----|---------|
| [Architecture.md](Architecture.md) | System vs Module model layering, repository pattern, interface bindings, base classes |
| [Conventions.md](Conventions.md) | Routes (middleware, prefix, PUT vs POST), soft-delete + FK RESTRICT, file naming, Pint workflow |
| [Frontend.md](Frontend.md) | Pages structure, Inertia conventions, TypeScript types, dark mode, data tables |
| [Enums.md](Enums.md) | All backend enums and their integer values |
| [Database.md](Database.md) | Authoritative schema reference — every table, FK, index, migration |

### Modules
| Doc | Purpose |
|-----|---------|
| [VenuesModule.md](VenuesModule.md) | Venues + Address lifecycle (required address) |
| [ClubsModule.md](ClubsModule.md) | Clubs + Address lifecycle (nullable address) + logo uploads |
| [ClubPlayers.md](ClubPlayers.md) | Club membership history (sub-feature of Clubs) |
| [PlayersModule.md](PlayersModule.md) | Players (no nested relation; standard repository) |
| [TournamentsModule.md](TournamentsModule.md) | Tournament → Category → Bracket → Match → MatchCompetitor |

### Base layer
| Doc | Purpose |
|-----|---------|
| [../base_system/README.md](../base_system/README.md) | `base_system` package: BaseModel, BaseRepository, BaseController, BaseFilter, code generator |

## Conventions for this wiki

- One topic per file. Keep filenames stable — they are linked from CLAUDE.md.
- Update the wiki in the same commit as the code change it describes.
- When a module wiki and `Database.md` conflict on schema, `Database.md` wins.
- When `Architecture.md` and a module wiki conflict on pattern, the **module wiki** wins (it reflects actual code).
