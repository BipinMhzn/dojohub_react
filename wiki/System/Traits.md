# System Foundation Traits

**Location:** `app/System/Foundation/Traits/`

Two controller traits shared across modules that need file uploads or avatar generation.

---

## FileUpload

**File:** `app/System/Foundation/Traits/FileUpload.php`  
**Used by:** `ClubsController`, `PlayersController`, `TournamentsController`

### Usage

```php
use App\System\Foundation\Traits\FileUpload;

final class ClubsController extends BaseWebController
{
    use FileUpload;

    private string $uploadDirectory = '/images/clubs';
}
```

### Methods

#### `uploadFile(UploadedFile $file, string $storePath = '', bool $public = true): array`

Stores one file to disk and returns its metadata.

- If `$storePath` is empty, auto-routes images to `config('fileupload.image_directory')` and other mime types to `config('fileupload.document_directory')`
- If `$public = true`, uses `storePublicly()` so the file is publicly accessible
- Returns: `['name' => string, 'path' => string, 'mime_type' => string]`

**Typical controller use:**

```php
if ($request->file('logo')) {
    $upload = $this->uploadFile($request->file('logo'), $this->uploadDirectory);
    $inputs['logo_url'] = $upload['path'];
    unset($inputs['logo']);
}
```

#### `multipleUpload(Request $request, string $storePath = '', bool $public = true, string $fileKey = 'files'): array`

Batch version — iterates `$request->file($fileKey)` (array) and calls `uploadFile()` for each. Returns array of metadata arrays.

#### `generateThumbnail(string $url): string`

Reads an image from `$url`, resizes it to `config('fileupload.thumb_width')` maintaining aspect ratio, stores it at `config('fileupload.image_thumb_directory')/{original-name}{thumb_suffix}`, returns the public storage URL.

Throws `App\Exceptions\FileNotFoundException` if the source file cannot be read.

---

## AvatarGenerator

**File:** `app/System/Foundation/Traits/AvatarGenerator.php`  
**Used by:** any controller or service that needs initials-based avatar URLs

### Method

#### `generateAvatar(string $name): string`

Extracts first letter of each word from `$name`, URL-encodes them, and returns a [ui-avatars.com](https://ui-avatars.com) URL.

**Example:**
```php
$this->generateAvatar('John Smith');
// → "https://ui-avatars.com/api/?name=J+S&color=1A237E&background=EBF4FF"
```

Colors are hard-coded (indigo text on light-blue background).

---

## File storage config keys

| Config key | Default | Purpose |
|------------|---------|---------|
| `fileupload.image_directory` | `/images` | Default image upload path |
| `fileupload.document_directory` | `/documents` | Default document upload path |
| `fileupload.image_thumb_directory` | `/images/thumbs` | Thumbnail output path |
| `fileupload.thumb_width` | `300` | Max thumbnail width in px |
| `fileupload.thumb_suffix` | `_thumb` | Appended before extension on thumbnails |

---

## Related

- [file-uploads.md](../../.claude/guidelines/file-uploads.md) — full upload workflow
- [System Index](Index.md)
