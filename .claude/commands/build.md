# Build

Vite + asset build commands for dojohub_react.

---

## Production build

```bash
npm run build
```

Compiles JS/CSS to `public/build/`. Used by Laravel's `@vite` directive in production. Run before deploys or whenever you need to verify a production-style asset bundle.

---

## Production build with SSR

```bash
npm run build:ssr
```

Builds both the client bundle AND the SSR bundle (for `php artisan inertia:start-ssr`). Used in production with SSR enabled.

---

## Development (HMR)

```bash
npm run dev
```

Starts the Vite dev server. Hot-module replacement is live as you edit files. The page reloads if Inertia detects a backend change.

Use **only** in development — production must use `npm run build` artefacts.

---

## TypeScript check

```bash
npm run types
```

Runs `tsc --noEmit` against the project. Strict-mode TypeScript catches:
- Missing return types
- Untyped props
- `null` / `undefined` access on optional fields
- Mismatched argument types

**Run before committing** when you've touched any `.tsx` / `.ts` file. CI runs this; failing means red build.

---

## Common workflows

| When | Run |
|------|-----|
| Starting daily dev | `npm run dev` (or `composer dev` for all-in-one) |
| Before committing TS changes | `npm run types && npm run lint` |
| Before pushing | `composer test` |
| Before merging to master | `npm run build` to verify production bundle works |
| After pulling main | `npm install && npm run dev` |

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| User reports stale UI | Ask if they ran `npm run dev` or `npm run build` |
| TypeScript errors on CI but not locally | Run `npm run types` locally — strict mode catches more than the editor |
| Production page is blank | Did you run `npm run build`? `public/build/manifest.json` must exist |
| HMR keeps disconnecting | Restart `npm run dev`; check for port conflicts |
| `@/` imports not resolving | Verify `tsconfig.json` paths AND `vite.config.ts` resolve.alias |

---

## Related

- [dev-server.md](dev-server.md) — Running the app locally
- [lint.md](lint.md) — Code quality checks
- [.claude/guidelines/frontend-architecture.md](../guidelines/frontend-architecture.md) — Vite/Tailwind setup
