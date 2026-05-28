# Settings Pages — Wiki

## Overview

The Settings module provides three pages for managing the authenticated user's account preferences. All three use `AppLayout` as the outer wrapper and `SettingsLayout` as the inner content wrapper. `SettingsLayout` renders a sidebar navigation with three entries: **Profile** (`/settings/profile`), **Password** (`/settings/password`), and **Appearance** (`/settings/appearance`).

None of these pages use `TournamentLayout`, `CategoryLayout`, or any other sub-layout — only `AppLayout` + `SettingsLayout`.

---

## Pages

### `pages/settings/profile.tsx` — Profile

**Inertia Props**:
```typescript
{ mustVerifyEmail: boolean; status?: string }
```

User data is not passed as a prop; it is read from Inertia's shared page data via `usePage<SharedData>().props.auth.user`.

**Layout**: `AppLayout` wrapping `SettingsLayout`

**Breadcrumbs**: Profile settings (`/settings/profile`)

**Components**: `HeadingSmall`, `Input`, `Button`, `InputError`, `Transition` (headlessui), `Link` (Inertia)

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `name` | Text | Required; `autoComplete="name"`; pre-filled from `auth.user.name` |
| `email` | `email` | `autoComplete="username"`; pre-filled from `auth.user.email`; **disabled** (read-only in the UI — cannot be edited by the user) |

**Behaviour**:
- Submits via `PATCH route('profile.update')` with `preserveScroll: true`.
- Email field is rendered as disabled — the user can see their email but cannot change it through this form.
- **Email verification banner**: shown only when `mustVerifyEmail === true` AND `auth.user.email_verified_at === null`. Displays "Your email address is unverified." with an inline Inertia `Link` (`method="post"` to `route('verification.send')`) to resend the verification email.
- When `status === 'verification-link-sent'` (set by the server after the resend), a green confirmation message "A new verification link has been sent to your email address." is shown.
- **Success indicator**: a `Transition`-animated "Saved" text appears briefly when `recentlySuccessful` is true (Inertia's `useForm` feature).

---

### `pages/settings/password.tsx` — Password

**Inertia Props**: None (no server-supplied props beyond shared data)

**Layout**: `AppLayout` wrapping `SettingsLayout`

**Breadcrumbs**: Password settings (`/settings/password`)

**Components**: `HeadingSmall`, `Input`, `Button`, `InputError`, `Transition` (headlessui)

**Form fields**:

| Field | Input type | Notes |
|---|---|---|
| `current_password` | `password` | `autoComplete="current-password"`; placeholder "Current password"; cleared on error |
| `password` | `password` | New password; `autoComplete="new-password"`; placeholder "New password"; cleared on error |
| `password_confirmation` | `password` | `autoComplete="new-password"`; placeholder "Confirm password"; cleared on error |

**Behaviour**:
- Submits via `PUT route('password.update')` with `preserveScroll: true`.
- **Error recovery**: on failure, the form intelligently resets only the fields that errored and focuses the relevant input:
  - If `errors.password` is set: clears `password` and `password_confirmation`, focuses the new password input (`passwordInput` ref).
  - If `errors.current_password` is set: clears `current_password`, focuses the current password input (`currentPasswordInput` ref).
- On success, all three fields are reset.
- **Success indicator**: same `Transition`-animated "Saved" text as the Profile page.

---

### `pages/settings/appearance.tsx` — Appearance

**Inertia Props**: None (no server-supplied props)

**Layout**: `AppLayout` wrapping `SettingsLayout`

**Breadcrumbs**: Appearance settings (`/settings/appearance`)

**Components**: `HeadingSmall`, `AppearanceTabs` (internal component)

**Behaviour**:
- No form submission — all changes are applied client-side without a server round-trip.
- `AppearanceTabs` renders three toggle buttons: **Light**, **Dark**, **System** (with Sun, Moon, Monitor icons respectively).
- Powered by the `useAppearance` hook (`resources/js/hooks/use-appearance.tsx`):
  - Current appearance is read from `localStorage` (key `'appearance'`) on mount.
  - `updateAppearance(mode)` stores the value in both `localStorage` and a cookie (`appearance`, 365-day expiry, `SameSite=Lax`) for SSR awareness, and immediately applies the theme by toggling the `dark` class on `document.documentElement`.
  - `system` mode uses `window.matchMedia('(prefers-color-scheme: dark)')` to follow the OS preference, with a listener that updates the theme dynamically when the OS setting changes.
- Possible values for `Appearance` type: `'light' | 'dark' | 'system'`.

---

## Navigation Map

| Action | URL | Method |
|---|---|---|
| Profile settings page | `/settings/profile` | GET |
| Update profile | `route('profile.update')` | PATCH |
| Password settings page | `/settings/password` | GET |
| Update password | `route('password.update')` | PUT |
| Appearance settings page | `/settings/appearance` | GET |
| Resend verification email | `route('verification.send')` | POST |
