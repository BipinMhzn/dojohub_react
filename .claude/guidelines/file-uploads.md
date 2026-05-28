# File Uploads Guideline

How to handle file uploads in dojohub_react. Used by club logos, player photos, etc.

---

## TL;DR

```php
use App\System\Foundation\Traits\FileUpload;

final class ClubsController extends BaseWebController
{
    use FileUpload;

    private string $uploadDirectory = '/images/clubs';

    public function store(ClubCreateRequest $request): RedirectResponse
    {
        $inputs = $request->validated();
        if ($request->file('logo')) {
            $profilePhoto = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
            $inputs['logo_url'] = $profilePhoto['path'];
            unset($inputs['logo']);
        }
        $club = $this->repository->createClub($inputs);
        return to_route('clubs.show', $club->id)->with('success', 'Club created successfully.');
    }
}
```

Reference: `app/Modules/Clubs/Controllers/ClubsController.php`.

---

## Where uploads live in the request flow

```
1. Frontend (React + Inertia useForm) sends multipart POST
2. Form Request validates the file (mimes, max size)
3. Controller picks up $request->file('field')
4. uploadFile() trait stores it (returns the public path)
5. Controller replaces 'field' (UploadedFile) with 'field_url' (string) in $validated
6. unset($validated['field']) — strip the UploadedFile
7. Repository writes the path to DB (e.g. logo_url column)
```

**Critical:** the repository **never** sees the `UploadedFile` object. The controller does the file work, the repository does the DB work.

---

## Validation rules

Always validate uploads in the Form Request. Use Laravel's `mimes` rule (not `mimetypes`) and a hard size cap.

```php
// ClubCreateRequest::rules()
return [
    'logo' => ['nullable', 'mimes:jpeg,jpg,png,gif,svg', 'max:'.(5 * 1024)],  // 5 MB
    // ... other fields
];
```

| Subject | Mimes | Max | Field name |
|---------|-------|-----|------------|
| Club logo | jpeg,jpg,png,gif,svg | 5 MB (`5 * 1024`) | `logo` |
| Player profile photo | jpeg,jpg,png | 5 MB | `profile_photo` |
| Tournament banner | jpeg,jpg,png | 10 MB | `banner` |

`max` is in **kilobytes**. The `5 * 1024` form makes the unit visible at a glance.

---

## Storage directories

Each module declares its own directory at the controller level:

```php
private string $uploadDirectory = '/images/clubs';
```

Convention: `/images/{module-plural}` for images, `/documents/{module-plural}` for documents. Leading slash is intentional — `storePublicly()` interprets it relative to the public disk.

| Subject | Directory |
|---------|-----------|
| Club logos | `/images/clubs` |
| Player photos | `/images/players` |
| Tournament banners | `/images/tournaments` |

---

## The `FileUpload` trait

Source: `app/System/Foundation/Traits/FileUpload.php`.

```php
public function uploadFile(UploadedFile $file, string $storePath = '', bool $public = true): array
{
    // Stores file, returns ['path' => 'public/path/to/file', ...]
}

public function multipleUpload(Request $request, string $storePath = '', bool $public = true, string $fileKey = 'files'): array
{
    // Loops $request->file($fileKey) and calls uploadFile() for each
}
```

The returned array's `path` key is what you persist to the DB (`logo_url`, `profile_photo_url`, etc.).

**Public vs private:**
- `$public = true` (default) → stored on the public disk, URL accessible
- `$public = false` → stored on the private disk, requires signed URL to fetch

For user-uploaded media (logos, photos), use **public**.

---

## POST-for-update with file uploads

Resource routes that accept file uploads must use **POST** (not PUT) because HTML forms with `enctype="multipart/form-data"` only allow POST. Inertia's `router.post()` with `_method` field handles the spoofing.

```php
// In Routes/web.php
Route::resource('clubs', ClubsController::class)->except(['update'])->names('clubs');
Route::post('clubs/{club}', [ClubsController::class, 'update'])->name('clubs.update');
```

On the frontend, `useForm` + `.post()` with `_method: 'put'` or via `router.put()` works transparently:

```tsx
const { data, setData, post, processing, errors } = useForm({
    name: '',
    logo: null as File | null,
});

const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('clubs.update', club.id), { forceFormData: true });
};
```

See [forms-and-validation.md](forms-and-validation.md) for the full form pattern.

---

## Updating uploads — handle "no new file" case

On update, the user might re-submit the form **without** changing the file. Don't wipe the existing `logo_url` in that case:

```php
public function update(ClubUpdateRequest $request, string $club): RedirectResponse
{
    $inputs = $request->validated();

    if ($request->file('logo')) {
        $profilePhoto = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
        $inputs['logo_url'] = $profilePhoto['path'];
        unset($inputs['logo']);
    }
    // If no new file: $inputs['logo_url'] is NOT set, so update() merges only changed fields

    $this->repository->updateClub($inputs, $club);
    return to_route('clubs.show', $club)->with('success', 'Club updated successfully.');
}
```

In the repository:

```php
$club->update([
    'logo_url' => $validated['logo_url'] ?? $club->logo_url,   // fall back to existing
    // ... other fields
]);
```

---

## Cleanup on delete

The current convention is **not** to delete physical files when a row is soft-deleted — the audit log + file URL are kept for forensics. Hard-delete operations (rare) should clean up via `Storage::delete($path)` if you implement them.

If a future requirement adds cleanup, do it in the repository's `deleteX()` method, not the controller.

---

## Frontend: file input + preview

For a one-file input with preview:

```tsx
const [logoPreview, setLogoPreview] = useState<string | null>(null);
const { data, setData, post, errors } = useForm({ logo: null as File | null });

const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0] ?? null;
    setData('logo', file);
    setLogoPreview(file ? URL.createObjectURL(file) : null);
};

<input type="file" accept="image/*" onChange={handleFileChange} />
{logoPreview && <img src={logoPreview} alt="Logo preview" />}
<InputError message={errors.logo} />
```

For dirty-state forms with existing logos, show the existing URL when no new file is selected.

---

## Pitfalls

| Pitfall | Avoidance |
|---------|-----------|
| Repository receives an `UploadedFile` | Controller must `unset($inputs['logo'])` after extracting `logo_url` |
| `Route::resource()` PUT used with file upload | Use POST-for-update + explicit route |
| `mimetypes` rule (browser-reported) instead of `mimes` (extension) | Stick with `mimes` — it's stricter |
| Forgetting `max:` size cap | Always cap; default PHP limit is 2 MB which silently truncates |
| Saving the `path` as full URL | Save the relative path; build URLs from `Storage::url($path)` at render time |
| Update overwrites `logo_url` with NULL when no file uploaded | Use `??` fall-back pattern in the repository |
| Not setting `forceFormData: true` in Inertia | When the form has a File, set this flag — Inertia otherwise sends JSON and the file disappears |

---

## Related docs

- [forms-and-validation.md](forms-and-validation.md) — Form patterns including file uploads
- [wiki/Conventions.md](../../wiki/Conventions.md#routes) — PUT vs POST rules
- `app/System/Foundation/Traits/FileUpload.php` — Trait source
- `app/Modules/Clubs/Controllers/ClubsController.php` — Reference implementation
