# Dashboard Page — Wiki

## Overview

The Dashboard is a minimal landing page shown to authenticated users after login. It serves as the application home screen and currently displays only a welcome heading with no dynamic data.

---

## Page

### `pages/dashboard.tsx` — Dashboard

**Inertia Props**: None (no server-supplied props)

**Layout**: `AppLayout`

**Breadcrumbs**: Dashboard (`/dashboard`)

**Components**: `Heading`

**Behaviour**:
- Renders a static `Heading` with the title "Welcome to DojoHub".
- No data is fetched or displayed. No actions are available on this page.
- The page is a placeholder — it is the default authenticated route and future dashboard widgets would be added here.

**Navigation**: Entry point after login. Accessible at `/dashboard`.
