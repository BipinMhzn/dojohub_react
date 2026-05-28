# BaseWebController — Wiki

**File**: `base_system/src/Controllers/BaseWebController.php`  
**Namespace**: `BaseSystem\Controllers`  
**Extends**: `App\Http\Controllers\Controller`

---

## Purpose

Base controller for all Inertia.js web-facing module controllers. Provides a single protected helper `render()` that wraps `Inertia::render()`, ensuring a consistent return type annotation and allowing future shared-prop injection at the base level.

---

## Class Definition

```php
class BaseWebController extends Controller
{
    protected function render(string $component, array $props = []): Response
    {
        return Inertia::render($component, $props);
    }
}
```

---

## Usage

Every web module controller extends `BaseWebController` and calls `$this->render()` instead of `Inertia::render()` directly:

```php
class VenuesController extends BaseWebController
{
    public function index(VenueListRequest $request): Response
    {
        $venues = $this->repository->getList($request->validated());
        return $this->render('venues/index', [
            'venues' => $venues,
        ]);
    }
}
```

---

## Return Type

`Inertia\Response` — **not** `Illuminate\Http\Response`. Inertia middleware converts this to either:
- A full page HTML response (first visit / SSR)
- A JSON `{ component, props, url, version }` response (subsequent Inertia navigations)

---

## Contrast: BaseController (JSON API)

| | `BaseWebController` | `BaseController` |
|---|---|---|
| Response type | `Inertia\Response` | `JsonResponse` |
| Used by | All web modules | Users module (API) |
| Key method | `render()` | `responseOk()`, `responseNotFound()`, etc. |
| SSR support | Yes | N/A |

---

## Related

- [BaseController](BaseController.md) — JSON API variant
- [HandleInertiaRequests](../Http/Middleware/HandleInertiaRequests.md) — middleware that enriches Inertia responses with shared props
