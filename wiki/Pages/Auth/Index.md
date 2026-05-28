# Auth Pages — Wiki

## Overview

The Auth module contains six pages covering the full authentication flow. All pages use `AuthLayout`, which renders a centred split-panel layout (`auth-split-layout`). Each page is a standalone Inertia component that passes `title` and `description` strings to `AuthLayout` as the panel header.

**Active routes** (enabled in `routes/auth.php`):
- `GET /login` — Login
- `POST /login` — Login submit
- `GET /verify-email` — Email verification prompt
- `GET /verify-email/{id}/{hash}` — Email verification link (signed)
- `POST /email/verification-notification` — Resend verification email
- `GET /confirm-password` — Confirm password
- `POST /confirm-password` — Confirm password submit
- `POST /logout` — Logout

**Disabled routes** (commented out in `routes/auth.php`):
- `GET/POST /register` — Registration
- `GET/POST /forgot-password` — Forgot password
- `GET/POST /reset-password/{token}` — Reset password

The component files for the disabled routes exist and are complete but their routes are not registered; they cannot be reached in the running application.

---

## Pages

### `pages/auth/login.tsx` — Login

**Inertia Props**:
```typescript
interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}
```

**Layout**: `AuthLayout` — title "Log in to your account", description "Enter your email and password below to log in"

**Components**: `Input`, `Checkbox`, `Button`, `TextLink`, `InputError`

**Form fields**:

| Field | Type | Notes |
|---|---|---|
| `email` | `email` input | Required; `autoFocus`; `autoComplete="email"`; placeholder "email@example.com" |
| `password` | `password` input | Required; `autoComplete="current-password"`; cleared via `reset('password')` on finish |
| `remember` | Checkbox | "Remember me"; defaults to `false` |

**Behaviour**:
- Submits to `route('login')` via `POST`.
- `canResetPassword` prop: when `true`, a "Forgot password?" `TextLink` is rendered next to the Password label, linking to `route('password.request')`. Since the forgot-password route is disabled, this link is non-functional in current deployments.
- `status` prop: when set, a green success message is displayed below the form (e.g., after a password reset).
- The "Don't have an account? Sign up" link is commented out in the JSX.
- Password field is reset on form completion (whether success or error).

**Navigation**: On success, redirects to the intended page (handled server-side). "Forgot password?" links to `password.request` (disabled).

---

### `pages/auth/confirm-password.tsx` — Confirm Password

**Inertia Props**: None (no server-supplied props beyond shared data)

**Layout**: `AuthLayout` — title "Confirm your password", description "This is a secure area of the application. Please confirm your password before continuing."

**Components**: `Input`, `Button`, `InputError`

**Form fields**:

| Field | Type | Notes |
|---|---|---|
| `password` | `password` input | `autoFocus`; `autoComplete="current-password"`; placeholder "Password"; cleared on finish |

**Behaviour**:
- Submits to `route('password.confirm')` via `POST`.
- Used when Laravel's `password.confirm` middleware requires re-authentication before accessing a sensitive section (e.g., Settings > Password).
- Password is reset via `reset('password')` after submission.

---

### `pages/auth/verify-email.tsx` — Verify Email

**Inertia Props**:
```typescript
{ status?: string }
```

**Layout**: `AuthLayout` — title "Verify email", description "Please verify your email address by clicking on the link we just emailed to you."

**Components**: `Button`, `TextLink`

**Form fields**: No input fields — a single "Resend verification email" submit button.

**Behaviour**:
- Submits to `route('verification.send')` via `POST` to trigger a new verification email.
- When `status === 'verification-link-sent'`, a green confirmation message is shown: "A new verification link has been sent to the email address you provided during registration."
- A "Log out" `TextLink` (method `post` to `route('logout')`) is rendered below the button.

---

### `pages/auth/forgot-password.tsx` — Forgot Password (DISABLED)

> **Note**: The route `GET /forgot-password` is commented out in `routes/auth.php`. This page exists but cannot be reached in the running application.

**Inertia Props**:
```typescript
{ status?: string }
```

**Layout**: `AuthLayout` — title "Forgot password", description "Enter your email to receive a password reset link"

**Form fields**:

| Field | Type | Notes |
|---|---|---|
| `email` | `email` input | `autoFocus`; `autoComplete="off"`; placeholder "email@example.com" |

**Behaviour**:
- Would submit to `route('password.email')` via `POST`.
- `status` prop displays a green success message when set.
- "Or, return to log in" link → `route('login')`.

---

### `pages/auth/register.tsx` — Register (DISABLED)

> **Note**: The `GET /register` and `POST /register` routes are commented out in `routes/auth.php`. This page exists but cannot be reached in the running application.

**Inertia Props**: None (no server-supplied props)

**Layout**: `AuthLayout` — title "Create an account", description "Enter your details below to create your account"

**Form fields**:

| Field | Type | Notes |
|---|---|---|
| `name` | `text` input | Required; `autoFocus`; placeholder "Full name" |
| `email` | `email` input | Required; placeholder "email@example.com" |
| `password` | `password` input | Required; `autoComplete="new-password"`; placeholder "Password"; cleared on finish |
| `password_confirmation` | `password` input | Required; `autoComplete="new-password"`; placeholder "Confirm password"; cleared on finish |

**Behaviour**:
- Would submit to `route('register')` via `POST`.
- Both password fields are cleared on form completion.
- "Already have an account? Log in" link → `route('login')`.

---

### `pages/auth/reset-password.tsx` — Reset Password (DISABLED)

> **Note**: The `GET /reset-password/{token}` and `POST /reset-password` routes are commented out in `routes/auth.php`. This page exists but cannot be reached in the running application.

**Inertia Props**:
```typescript
interface ResetPasswordProps {
    token: string;
    email: string;
}
```

**Layout**: `AuthLayout` — title "Reset password", description "Please enter your new password below"

**Form fields**:

| Field | Type | Notes |
|---|---|---|
| `email` | `email` input | Pre-filled from `email` prop; `readOnly`; not user-editable |
| `password` | `password` input | `autoFocus`; `autoComplete="new-password"`; placeholder "Password"; cleared on finish |
| `password_confirmation` | `password` input | `autoComplete="new-password"`; placeholder "Confirm password"; cleared on finish |

`token` is included in the form data (from prop) but rendered as a hidden value — no visible input field.

**Behaviour**: Would submit to `route('password.store')` via `POST`.

---

## Navigation Map

| Action | URL | Method | Status |
|---|---|---|---|
| Show login | `/login` | GET | Active |
| Submit login | `/login` | POST | Active |
| Verify email prompt | `/verify-email` | GET | Active |
| Verify email link | `/verify-email/{id}/{hash}` | GET | Active (signed) |
| Resend verification | `/email/verification-notification` | POST | Active |
| Confirm password | `/confirm-password` | GET | Active |
| Submit confirm password | `/confirm-password` | POST | Active |
| Logout | `/logout` | POST | Active |
| Register | `/register` | GET/POST | **Disabled** |
| Forgot password | `/forgot-password` | GET/POST | **Disabled** |
| Reset password | `/reset-password/{token}` | GET/POST | **Disabled** |
