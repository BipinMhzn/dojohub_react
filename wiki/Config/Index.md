# Configuration — Wiki

---

## Overview

DojoHub uses standard Laravel configuration files in `config/` plus the `base-system` package config. Below is a reference for all configuration files — custom settings, relevant env vars, and DojoHub-specific behaviour.

---

## `config/app.php` — Application

| Key | Env var | Default | Notes |
|-----|---------|---------|-------|
| `name` | `APP_NAME` | `Laravel` | Shared to all pages as `name` Inertia prop. Set to `DojoHub` |
| `env` | `APP_ENV` | `production` | `local` for development |
| `debug` | `APP_DEBUG` | `false` | Enable for local dev only |
| `url` | `APP_URL` | `http://localhost` | `http://dojohub_react.test` locally |
| `timezone` | — | `UTC` | System timezone for all PHP date functions |
| `locale` | — | `en` | Application language |
| `key` | `APP_KEY` | — | Must be set — `php artisan key:generate` |

---

## `config/audit.php` — Laravel Auditing (owen-it/laravel-auditing)

All domain models extend `AppBaseModel` which includes `Auditable`. This package records every create/update/delete into the `audits` table.

| Key | Default | Notes |
|-----|---------|-------|
| `enabled` | `env('AUDITING_ENABLED', true)` | Set `AUDITING_ENABLED=false` to disable in tests |
| `implementation` | `OwenIt\Auditing\Models\Audit::class` | Audit model |
| `user.morph_prefix` | `'user'` | Column prefix in `audits` table |
| `user.guards` | `['web', 'api']` | Guards checked for the acting user |
| `events` | `['created', 'updated', 'deleted', 'restored']` | Eloquent events that trigger audits |

The `audits` migration is at `database/migrations/0001_01_01_000003_create_audits_table.php`.

---

## `config/auth.php` — Authentication

| Key | Default | Notes |
|-----|---------|-------|
| `defaults.guard` | `'web'` | Session-based auth |
| `defaults.passwords` | `'users'` | Password reset broker |
| `guards.web.driver` | `'session'` | — |
| `guards.web.provider` | `'users'` | — |
| `providers.users.driver` | `'eloquent'` | — |
| `providers.users.model` | `App\System\User\Models\User::class` | — |

Standard Laravel Breeze session auth. No API guard configured.

---

## `config/cache.php` — Cache

| Key | Env var | Default | Notes |
|-----|---------|---------|-------|
| `default` | `CACHE_STORE` | `'database'` | Use `file` for local dev |
| `stores.database` | — | — | Stores in `cache` table |
| `stores.file` | — | `storage/framework/cache` | File-based cache |
| `prefix` | `CACHE_PREFIX` | `laravel_cache` | — |

---

## `config/database.php` — Database

| Key | Env var | Default | Notes |
|-----|---------|---------|-------|
| `default` | `DB_CONNECTION` | `'sqlite'` | `mysql` for local/production |
| MySQL `host` | `DB_HOST` | `127.0.0.1` | — |
| MySQL `port` | `DB_PORT` | `3306` | — |
| MySQL `database` | `DB_DATABASE` | `laravel` | `dojohub_react` locally |
| MySQL `username` | `DB_USERNAME` | `root` | — |
| MySQL `password` | `DB_PASSWORD` | — | — |

---

## `config/filesystems.php` — File Storage

| Disk | Driver | Root | Notes |
|------|--------|------|-------|
| `local` | `local` | `storage/app/private` | Private files |
| `public` | `local` | `storage/app/public` | Publicly accessible; symlinked to `public/storage` |
| `s3` | `s3` | — | AWS S3 (optional) |

**Default disk**: `env('FILESYSTEM_DISK', 'local')`

File uploads (club logos, player photos) use the `public` disk. URLs are built with `env('APP_URL') . '/storage'`.

Run `php artisan storage:link` to create the `public/storage` symlink.

---

## `config/inertia.php` — Inertia.js

| Key | Value | Notes |
|-----|-------|-------|
| `ssr.enabled` | `true` | SSR is enabled |
| `ssr.url` | `http://127.0.0.1:13714` | Node SSR server URL |
| `testing.ensure_pages_exist` | `true` | Component path validation in tests |
| `testing.page_paths` | `[resource_path('js/pages')]` | Where Inertia looks for page components |
| `testing.page_extensions` | `['js','jsx','ts','tsx',...]` | Accepted component extensions |

---

## `config/logging.php` — Logging

| Key | Env var | Default | Notes |
|-----|---------|---------|-------|
| `default` | `LOG_CHANNEL` | `'stack'` | Use `single` for local dev |
| `deprecations.channel` | `LOG_DEPRECATIONS_CHANNEL` | `'null'` | — |

Stack channel uses `daily` (14-day retention) + `stderr`.

---

## `config/mail.php` — Mail

| Key | Env var | Notes |
|-----|---------|-------|
| `default` | `MAIL_MAILER` | `log` for local dev, `smtp`/`ses` for production |
| `from.address` | `MAIL_FROM_ADDRESS` | Sender address |
| `from.name` | `MAIL_FROM_NAME` | Sender name (defaults to `APP_NAME`) |

---

## `config/permission.php` — Spatie Permissions

| Key | Value | Notes |
|-----|-------|-------|
| `models.permission` | `Spatie\Permission\Models\Permission::class` | — |
| `models.role` | `Spatie\Permission\Models\Role::class` | — |
| `table_names.roles` | `'roles'` | — |
| `table_names.permissions` | `'permissions'` | — |
| `table_names.model_has_permissions` | `'model_has_permissions'` | — |
| `table_names.model_has_roles` | `'model_has_roles'` | — |
| `table_names.role_has_permissions` | `'role_has_permissions'` | — |
| `teams` | `false` | Team-based permissions not used |

Route middleware aliases registered in `bootstrap/app.php`: `role`, `permission`, `role_or_permission`.

**Seeded roles** (via `RoleSeeder`): `super_admin`, `tournament_manager`, `club_admin`, `referee`, `coach`.

---

## `config/queue.php` — Queue

| Key | Env var | Default | Notes |
|-----|---------|---------|-------|
| `default` | `QUEUE_CONNECTION` | `'database'` | Use `sync` for local dev/tests |

---

## `config/session.php` — Session

| Key | Env var | Default | Notes |
|-----|---------|---------|-------|
| `driver` | `SESSION_DRIVER` | `'database'` | `file` or `cookie` for local dev |
| `lifetime` | `SESSION_LIFETIME` | `120` (minutes) | — |
| `encrypt` | `SESSION_ENCRYPT` | `false` | — |
| `secure` | `SESSION_SECURE_COOKIE` | `false` | Set `true` in production (HTTPS) |

---

## `base_system/config/base-system.php` — Base System Package

| Key | Value | Notes |
|-----|-------|-------|
| `module` | `'Modules'` | Where modules live: `app/Modules/` |
| `system` | `'System'` | Where system models live: `app/System/` |

Published as `config/base-system.php` via the package service provider.

---

## Environment Variables Summary

```env
# App
APP_NAME=DojoHub
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://dojohub_react.test

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dojohub_react
DB_USERNAME=root
DB_PASSWORD=

# Cache / Session / Queue
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=log

# Auditing
AUDITING_ENABLED=true
```

---

## Related

- [Database Migrations](../Database/Migrations.md) — schema migrations for all tables
- [Providers](../Providers/Index.md) — AppServiceProvider sets Password defaults
