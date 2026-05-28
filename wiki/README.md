# DojoHub Wiki

Authoritative project documentation. CLAUDE.md (project root) is the entry point and links here.

---

## Index

### Architecture & Standards
| Doc | Purpose |
|-----|---------|
| [Architecture.md](Architecture.md) | System vs Module model layering, repository pattern, interface bindings, base classes |
| [Conventions.md](Conventions.md) | Routes (middleware, prefix, PUT vs POST), soft-delete + FK RESTRICT, file naming, Pint workflow |
| [Enums.md](Enums.md) | All backend enums and their integer values |

### System Layer
| Doc | Purpose |
|-----|---------|
| [System/Index.md](System/Index.md) | All System models, AppBaseModel, traits, full enum table |
| [System/AppBaseModel.md](System/AppBaseModel.md) | AppBaseModel: HasUlids, SoftDeletes, EditorLogs, Auditable |
| [System/Traits.md](System/Traits.md) | FileUpload + AvatarGenerator traits |
| [System/Address.md](System/Address.md) | Address model (shared by clubs and venues) |
| [System/User.md](System/User.md) | User model (Spatie HasRoles, standard auth) |
| [System/Venue.md](System/Venue.md) + [VenueMat.md](System/VenueMat.md) | Venue + mat models |
| [System/Club.md](System/Club.md) | Club model + memberships |
| [System/Player.md](System/Player.md) | Player model |
| [System/Coach.md](System/Coach.md) + [Referee.md](System/Referee.md) | Coach + Referee models |
| [System/Tournament.md](System/Tournament.md) | Tournament hierarchy |
| [System/BeltRank.md](System/BeltRank.md) + [TournamentRuleSet.md](System/TournamentRuleSet.md) | Lookup models |

### BaseSystem Package
| Doc | Purpose |
|-----|---------|
| [BaseSystem/Index.md](BaseSystem/Index.md) | Package overview, structure, bootstrap registration |
| [BaseSystem/BaseSystemProvider.md](BaseSystem/BaseSystemProvider.md) | Module auto-discovery mechanism |
| [BaseSystem/BaseRepository.md](BaseSystem/BaseRepository.md) | CRUD repository: getList, create, update, destroy, events |
| [BaseSystem/BaseModel.md](BaseSystem/BaseModel.md) | Sort columns, pagination defaults |
| [BaseSystem/EditorLogs.md](BaseSystem/EditorLogs.md) | created_by/updated_by/deleted_by trait |
| [BaseSystem/BaseFilter.md](BaseSystem/BaseFilter.md) | attachFilterQuery dispatch mechanism |
| [BaseSystem/BaseInterface.md](BaseSystem/BaseInterface.md) | CRUD contract |
| [BaseSystem/BaseWebController.md](BaseSystem/BaseWebController.md) | Inertia controller base (render helper) |
| [BaseSystem/BaseController.md](BaseSystem/BaseController.md) | JSON API controller base |
| [BaseSystem/BaseWebRequest.md](BaseSystem/BaseWebRequest.md) | Web form request + list request base |
| [BaseSystem/SystemResponseTrait.md](BaseSystem/SystemResponseTrait.md) | JSON response helpers |
| [BaseSystem/SystemConstants.md](BaseSystem/SystemConstants.md) | ERR_* error code constants |
| [BaseSystem/ModuleGenerateCommand.md](BaseSystem/ModuleGenerateCommand.md) | php artisan base-system:generate |

### Modules
| Doc | Purpose |
|-----|---------|
| [Modules/Venues/Index.md](Modules/Venues/Index.md) | Venues + Mats (required address, no file upload) |
| [Modules/Clubs/Index.md](Modules/Clubs/Index.md) | Clubs + Players/Coaches memberships (logo upload) |
| [Modules/Players/Index.md](Modules/Players/Index.md) | Players (profile photo, belt rank, nationality) |
| [Modules/Coaches/Index.md](Modules/Coaches/Index.md) | Coaches (optional user link, club memberships) |
| [Modules/Referees/Index.md](Modules/Referees/Index.md) | Referees (certification level, nationality) |
| [Modules/Tournaments/Index.md](Modules/Tournaments/Index.md) | Tournament → Category → Registration → Bracket → Match |
| [Modules/Users/Index.md](Modules/Users/Index.md) | Users (JSON API module, not Inertia) |

### Http & Middleware
| Doc | Purpose |
|-----|---------|
| [Http/Index.md](Http/Index.md) | Http layer overview, middleware stack, controller map |
| [Http/Middleware/HandleInertiaRequests.md](Http/Middleware/HandleInertiaRequests.md) | Shared Inertia props (name, auth, flash, ziggy, sidebarOpen) |
| [Http/Middleware/HandleAppearance.md](Http/Middleware/HandleAppearance.md) | Appearance cookie → View::share |
| [Http/Controllers/Auth/Index.md](Http/Controllers/Auth/Index.md) | Login, logout, email verification controllers |
| [Http/Controllers/Settings/Index.md](Http/Controllers/Settings/Index.md) | Profile + password settings controllers |

### Infrastructure
| Doc | Purpose |
|-----|---------|
| [Providers/Index.md](Providers/Index.md) | AppServiceProvider + BaseSystemProvider + module bindings table |
| [Routes/Index.md](Routes/Index.md) | All route files, middleware groups, module route map |
| [Config/Index.md](Config/Index.md) | All config files with env vars |
| [Database/Migrations.md](Database/Migrations.md) | Full schema — every table, column, FK, index |
| [Database/Factories.md](Database/Factories.md) | All factories with fields and states |
| [Database/Seeders.md](Database/Seeders.md) | Seeder classes, run order, what each creates |
| [Database/EditorTrait.md](Database/EditorTrait.md) | Migration EditorTrait macro |
| [Tests/Index.md](Tests/Index.md) | Test structure, PHPUnit config, Unit vs Feature patterns |

### Frontend Core
| Doc | Purpose |
|-----|---------|
| [Frontend/Index.md](Frontend/Index.md) | Stack overview, entry points, path aliases, dark mode, SSR |
| [Frontend/Bootstrap.md](Frontend/Bootstrap.md) | `app.tsx` + `ssr.tsx` — CSR/SSR bootstrap flow, Ziggy polyfill |
| [Hooks/Index.md](Hooks/Index.md) | All 5 hooks: useAppearance, useFlashToast, useInitials, useIsMobile, useMobileNavigation |
| [Types/Index.md](Types/Index.md) | SharedData, User, FlashMessages, BreadcrumbItem, NavItem, PaginationLink, PageFilters |
| [Utils/Index.md](Utils/Index.md) | `formatUTCDate`, date converters, Nepal provinces/districts data |
| [Layouts/Index.md](Layouts/Index.md) | AppLayout, AuthLayout, sub-layouts (Tournament, Category, Venue, Club, Settings) |

### Pages (Frontend)
| Doc | Purpose |
|-----|---------|
| [Pages/Venues/Index.md](Pages/Venues/Index.md) | Venue list, create, show, edit, mats — props, form fields, column defs |
| [Pages/Clubs/Index.md](Pages/Clubs/Index.md) | Club list, show, create, players tab, coaches tab — full membership workflow |
| [Pages/Players/Index.md](Pages/Players/Index.md) | Player list, create, edit — profile photo, DOB picker, belt rank |
| [Pages/Coaches/Index.md](Pages/Coaches/Index.md) | Coach list, create, edit — active club badges, no photo |
| [Pages/Referees/Index.md](Pages/Referees/Index.md) | Referee list, create, edit — certification level enum handling |
| [Pages/Tournaments/Index.md](Pages/Tournaments/Index.md) | All 11 tournament pages — enums, bracket generation, match result flow, results |
| [Pages/Auth/Index.md](Pages/Auth/Index.md) | Login, verify-email, confirm-password (register/reset disabled) |
| [Pages/Settings/Index.md](Pages/Settings/Index.md) | Profile, password, appearance settings pages |
| [Pages/Dashboard.md](Pages/Dashboard.md) | Dashboard placeholder page |

### Components (Frontend)
| Doc | Purpose |
|-----|---------|
| [Components/Index.md](Components/Index.md) | All components overview — app components, UI library, SVG |
| [Components/DataTable.md](Components/DataTable.md) | DataTableControls + DataTable + DataTablePagination — full filter/sort/paginate pattern |
| [Components/AppSidebar.md](Components/AppSidebar.md) | AppSidebar, NavMain, NavUser — sidebar + nav item wiring |
| [Components/Brackets/Index.md](Components/Brackets/Index.md) | BracketContainer, SingleEliminationBracket, MatchCard, MatchDetailDialog |
| [Components/InputError.md](Components/InputError.md) | InputError, Heading, HeadingSmall — form error display + page title components |

### Operations
| Doc | Purpose |
|-----|---------|
| [Operations/Deployment.md](Operations/Deployment.md) | Local setup, build steps, SSR server, env vars, post-deploy checklist |
| [Operations/Troubleshooting.md](Operations/Troubleshooting.md) | Common backend + frontend issues with diagnosis and fixes |
| [Operations/Performance.md](Operations/Performance.md) | Code splitting, partial reloads, debounce, eager loading, caching |

---

## Conventions for this wiki

- One topic per file. Keep filenames stable — they are linked from CLAUDE.md.
- Update the wiki in the same commit as the code change it describes.
- When a module wiki and `Database/Migrations.md` conflict on schema, migrations win.
- When `Architecture.md` and a module wiki conflict on pattern, the **module wiki** wins (it reflects actual code).
- Legacy flat files (VenuesModule.md etc.) are superseded — use the structured `Modules/` docs instead.
