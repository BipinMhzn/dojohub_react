# Lint & Format

Code style enforcement for dojohub_react.

---

## Backend (PHP) — Laravel Pint

```bash
vendor/bin/pint --dirty            # Format only changed (uncommitted) files
vendor/bin/pint                    # Format ALL PHP files (rarely needed)
```

**Rules:**
- Run `vendor/bin/pint --dirty` **after every PHP change** you make
- **Never** use `--test` (read-only mode) — just run the formatter
- Pint enforces project-wide PHP style; trust its output and commit the changes

Config: Pint uses the Laravel preset by default. Project-specific overrides go in `pint.json` (none currently).

---

## Frontend — ESLint

```bash
npm run lint
```

Runs `eslint . --fix` — auto-fixes what it can, reports the rest. CI fails on any unfixed error.

Config: `eslint.config.js` (flat config). Rules come from:
- `@eslint/js` (recommended)
- `eslint-plugin-react` + `eslint-plugin-react-hooks`
- `typescript-eslint`
- `eslint-config-prettier` (turns off rules that conflict with Prettier)

---

## Frontend — Prettier

```bash
npm run format                      # Format files in resources/
npm run format:check                # Check (no write) — used in CI
```

Plugins (auto-applied):
- `prettier-plugin-organize-imports` — sorts and removes unused imports
- `prettier-plugin-tailwindcss` — sorts Tailwind classes by convention

**Don't fight the tooling** — if a class order looks "wrong", that's the canonical Tailwind order. Don't add `// prettier-ignore` unless absolutely necessary.

---

## Workflow

```bash
# After editing PHP files
vendor/bin/pint --dirty

# After editing TS/TSX files
npm run lint
npm run format
npm run types
```

Or as a one-liner for full pre-commit hygiene:

```bash
vendor/bin/pint --dirty && npm run lint && npm run format && npm run types
```

---

## When Pint disagrees with you

Pint enforces the project standard. If you have a strong reason for a different style:

1. Talk to the team.
2. Update `pint.json` (or create it) with the rule override.
3. Run `vendor/bin/pint` on the whole codebase.
4. Commit the formatter changes separately from your feature changes.

Don't sprinkle `// phpcs:ignore` or fight Pint per-file.

---

## Common pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Running `vendor/bin/pint` (no `--dirty`) on an unrelated branch | Formats ALL files; explodes the diff. Use `--dirty`. |
| Running `pint --test` thinking it'll fix things | `--test` is read-only; use plain `pint --dirty` |
| Skipping `npm run lint` because `npm run format` was clean | They check different things — Prettier is style, ESLint is correctness |
| Disabling a Tailwind class-sort manually | Trust the plugin |
| Committing without `npm run types` | Strict TS bugs slip through to CI |

---

## What's NOT configured

- **No EditorConfig watchdog** — the `.editorconfig` exists but isn't enforced by any tool
- **No `php-cs-fixer`** — Pint is the only PHP formatter
- **No `stylelint`** — Tailwind generates styles; no separate CSS to lint
- **No pre-commit hooks** — formatting is on the honour system

You are responsible for running these tools before pushing.

---

## Related

- [test.md](test.md) — Test runner
- [build.md](build.md) — TypeScript check
- [.claude/guidelines/typescript-conventions.md](../guidelines/typescript-conventions.md)
- [wiki/Conventions.md](../../wiki/Conventions.md#code-style)
