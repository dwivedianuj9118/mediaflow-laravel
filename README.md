# MediaFlow for Laravel

[![Tests](https://img.shields.io/badge/tests-97%20passed%20(500%20assertions)-brightgreen.svg)]()
[![Documentation](https://img.shields.io/badge/docs-GitHub%20Pages-4f46e5.svg)](https://dwivedianuj9118.github.io/mediaflow-laravel/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)]()
[![PHP](https://img.shields.io/badge/php-%5E8.2%20%7C%20%5E8.3%20%7C%20%5E8.4%20%7C%20%5E8.5-777bb4.svg)]()
[![Laravel](https://img.shields.io/badge/laravel-%5E11.0%20%7C%20%5E12.0%20%7C%20%5E13.0-ff2d20.svg)]()
[![Dependencies](https://img.shields.io/badge/external%20cdns-zero-success.svg)]()

Universal media management infrastructure, provider-agnostic storage adapters, CDN delivery resolution, modern Blade shortcut input components, on-demand format conversion & compression, and hardware-accelerated 3D upload lifecycle visualization for Laravel applications.

> 📖 **Live Documentation Portal**: [https://dwivedianuj9118.github.io/mediaflow-laravel/](https://dwivedianuj9118.github.io/mediaflow-laravel/) (or locally at `/mediaflow/docs`) for the complete developer guide: installation, components, configuration, API reference and security, one page per topic.

<p align="center">
  <img src="https://raw.githubusercontent.com/dwivedianuj9118/mediaflow-laravel/main/docs/public/images/mediaflow_architecture.svg" alt="MediaFlow System Reference Architecture" width="100%">
</p>

---

## Key Features

- **Shortcut Input Component (`<x-mediaflow-input>`)**: Single drop-in tag for form upload boxes with drag & drop, file selection, "Browse Library" picker modal, model pre-population, and hidden media ID binding.
- **Dynamic File Types**: Native support for images, videos (`type="video"`), documents/PDFs (`type="pdf"` or `type="docs"`), audio tracks (`type="audio"`), or any file type (`type="any"` or custom `accept="..."`) with rich preview cards.
- **Frontend Media Picker Isolation & Upload-Only Mode**: Prevent end-users or guest authors from viewing administrative assets using `:frontend="true"` (locks folder navigation) or `:upload-only="true"` (hides library browsing completely).
- **Fullscreen Inspection & 1-Click Copy URL**: Instantly open full-resolution images in a new browser tab or copy delivery/CDN URLs to clipboard with non-blocking toast notifications.
- **Side Admin Panel & Drawer Compatibility**: Built-in `:compact="true"` mode engineered for narrow administrative sidebars and drawers (280px–340px) with `z-index: 9999999 !important;` modal stacking isolation.
- **Universal PHP Upload API**: Single contract for handling uploads in controllers, queued jobs, or API endpoints (`MediaFlow::upload()`).
- **Decoupled Storage & Delivery**: Clean separation between physical persistence (Local disk, Amazon S3, Cloudflare R2, MinIO) and URL resolution (Disk URLs, custom CDN domains, or time-limited signed streaming).
- **On-Demand Compression & Conversion**: Convert images to **WebP**, **JPEG** or **PNG** with quality tuning, optional resizing and real-time savings metrics.
- **Secure by Default**: Authentication required out of the box, content-sniffed MIME validation, server-chosen file extensions, SVG script filtering, EXIF/GPS stripping, masked cloud credentials and optional per-user media scoping.
- **Access Control & Guard Support**: Protect media access using Laravel authentication guards (`web`, `admin`, `api`), roles (`admin`, `editor`), Spatie permissions, or custom closure callbacks (`MediaFlow::authorize(...)`).
- **3D Upload Lifecycle Visualizer**: Interactive 3D isometric animation illustrating the entire 5-station pipeline (Disk Pick → Quarantine Buffer → UUID Rename → GD/Imagick Variants → Storage & CDN).
- **Zero External Dependencies**: 100% self-contained vanilla CSS and JavaScript with zero external CDN dependencies, light/dark mode auto-detection, and system theme inheritance.

---

## Requirements

- **PHP**: `^8.2 | ^8.3 | ^8.4 | ^8.5` (with extensions: `fileinfo`, `gd` with WebP support; `exif` optional, used to keep JPEG orientation when metadata is stripped)
- **Laravel Framework**: `^11.0 | ^12.0 | ^13.0`
- **Database**: SQLite 3.35+, MySQL 8.0+, or PostgreSQL 14+

---

## Installation

### 1. Require via Composer
```bash
composer require dwivedianuj9118/mediaflow-laravel
```
> ⚡ **Cloud Ready Out-of-the-Box**: Cloudflare R2, AWS S3, MinIO, DigitalOcean Spaces, Wasabi, and Backblaze B2 driver dependencies (`league/flysystem-aws-s3-v3`) are bundled directly with MediaFlow—no separate packages or driver configurations required!

### 2. Publish Configuration, Migrations & Assets
```bash
# Publish configuration file (config/mediaflow.php)
php artisan vendor:publish --tag=mediaflow-config

# Publish database migrations
php artisan vendor:publish --tag=mediaflow-migrations

# Publish native CSS/JS assets (to public/vendor/mediaflow)
php artisan vendor:publish --tag=mediaflow-assets
```

### 3. Run Migrations & Link Storage
```bash
php artisan migrate
php artisan storage:link
```

In production, rebuild caches after installing or updating MediaFlow so its config is included:

```bash
php artisan optimize        # or: php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan vendor:publish --tag=mediaflow-assets --force
```

### 4. Sign in (authentication is required by default)
All MediaFlow management routes require a logged-in user. Restrict them further with roles, permissions or a Gate (see [Access Control](#access-control-guards--roles)). For a quick local demo without login only:

```env
MEDIAFLOW_AUTH_ENABLED=false   # never in production
```

### 5. Updating an Existing Project
Whenever a new release or update is published, update any existing Laravel project with this single 1-line command:

```bash
composer update dwivedianuj9118/mediaflow-laravel && php artisan vendor:publish --tag=mediaflow-assets --force && php artisan optimize:clear
```
- `composer update dwivedianuj9118/mediaflow-laravel`: Downloads the latest package version or commits into your project.
- `php artisan vendor:publish --tag=mediaflow-assets --force`: Overwrites and syncs `mediaflow.js` and `mediaflow.css` in `public/vendor/mediaflow`.
- `php artisan optimize:clear`: Flushes old route and configuration caches so new features take effect immediately.

---

## Quick Usage: Blade Form Input Box

Drop a complete media upload and library picker box into any Blade form using `<x-mediaflow-input>`:

```blade
<form method="POST" action="/articles">
    @csrf

    <div class="form-group">
        <label>Article Title</label>
        <input type="text" name="title" class="form-control" required>
    </div>

    {{-- Drop-in MediaFlow Input Component --}}
    <x-mediaflow-input 
        name="cover_media_id" 
        label="Featured Cover Image"
        help="Drag & drop an image or select from your media library."
        :required="true"
    />

    <button type="submit" class="btn btn-primary">Publish</button>
</form>
```

*(Note: The alias `<x-mediaflow::input ... />` is also registered and interchangeable.)*

### Side Admin Panel / Drawer Mode (`:compact="true"`)
When using inside narrow admin sidebars, off-canvas drawers, or column widgets (280px–340px):

```blade
<div class="admin-sidebar-drawer" style="width: 320px;">
    <h3>Quick Post Settings</h3>

    <x-mediaflow-input 
        name="sidebar_thumbnail_id" 
        label="Sidebar Thumbnail"
        :compact="true"
        help="Optimized for narrow sidebars."
    />
</div>
```

### Pre-populating Existing Media on Edit
Pass an existing `Media` Eloquent model, integer ID, or URL string:

```blade
<x-mediaflow-input 
    name="cover_media_id" 
    label="Cover Image"
    :value="$post->coverMedia ?? old('cover_media_id')" 
/>
```

### Component Props Reference

| Prop | Type | Default | Description |
|---|---|---|---|
| `name` | `string` | *(required)* | Form field name submitted in `POST` requests. |
| `label` | `string` | `null` | Field label displayed above dropzone. |
| `value` | `mixed` | `null` | Initial media selection (`Media` model, ID integer, or URL). |
| `compact` | `bool` | `false` | Adapts layout and dimensions for narrow admin panels (≤ 340px). |
| `picker` / `browse` | `bool` | `true` | Displays "🗂 Browse Library" button to select existing media (`:browse="false"` or `hide-browse` hides it). |
| `uploadOnly` | `bool` | `false` | Hides library browsing button entirely; only allows fresh uploads. |
| `frontend` / `isolated` | `bool` | `false` | Isolates picker strictly to specified folder/collection and hides root admin folders. |
| `pickerScope` | `string` | `'all'` | Library modal query scope (`'all'` or `'frontend'`). |
| `copyUrl` | `bool` | `true` | Displays 1-click clipboard copy button on preview card. |
| `openTab` | `bool` | `true` | Displays "Open in new tab" fullscreen preview button on preview card. |
| `accept` | `string` | `'image/*'` | Allowed MIME type filter for file chooser. |
| `folder` | `string` | `null` | Target folder slug/UUID for uploads from this field. |
| `collection`| `string` | `'default'` | Target media collection classification name. |
| `required` | `bool` | `false` | Adds required asterisk indicator and validation flags. |
| `help` | `string` | `null` | Helper text rendered beneath the dropzone. |
| `visibility` | `string` | `'public'` | `public` or `private` (private files get signed URLs). |
| `uploadUrl` | `string` | `route('mediaflow.upload')` | Custom upload endpoint. |
| `theme` / `mode` / `folderColor` | `string` | `null` | Per-component accent theme, `light`/`dark` mode and folder colour. |
| `pipeline` / `visualizer` | `bool` | `true` (from config) | Triggers the full-screen interactive 3D 5-station upload lifecycle visualizer modal upon drop/upload. |

### Frontend Media Picker Isolation & Security

To prevent end-users on frontend forms from seeing administrative images, logos, or other users' confidential documents:

```blade
{{-- Option A: Hide Browse Button (Upload-Only Mode) --}}
<x-mediaflow-input 
    name="avatar" 
    label="Profile Picture" 
    :browse="false" 
/>
{{-- Or: :picker="false", upload-only, or hide-browse --}}

{{-- Option B: Isolated Scoped Library (User only sees their collection/folder, admin folders hidden) --}}
<x-mediaflow-input 
    name="customer_receipt" 
    label="Proof of Purchase" 
    :frontend="true" 
    folder="customer-receipts" 
    collection="receipts" 
/>
```

### Fullscreen Preview & Copy URL Actions

Every preview card inside `<x-mediaflow-input>` includes:
- **`👁️ Open`**: Opens the full-resolution image in a new browser tab for fullscreen inspection.
- **`📋 Copy URL`**: Copies the exact public or CDN delivery URL directly to the user's clipboard and triggers a non-blocking toast notification.

The Explorer grid cards and Inspector sidebar also feature quick `Open` and `Copy URL` action buttons.

### Assets Directives
Add to your Blade master layout (`layouts/app.blade.php`):
```blade
<head>
    <!-- Mandatory CSRF Token for AJAX uploads & picker modal -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @mediaflowStyles
</head>
<body>
    @yield('content')

    @mediaflowScripts
</body>
```

### Backend Controller & Form Submission
When the user submits the form, `$request->input('cover_media_id')` contains the selected `media.id`:

```php
use App\Models\Post;
use Illuminate\Http\Request;

public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'cover_media_id' => 'nullable|integer|exists:mediaflow_media,id',
    ]);

    $post = Post::create([
        'title' => $validated['title'],
        'cover_media_id' => $validated['cover_media_id'],
    ]);

    return redirect()->back()->with('success', 'Post saved!');
}
```

---

## Standalone Media Gallery Component

To embed the pure media library / gallery inside any custom admin view, modal, or dashboard tab without wrapper navbars:

```blade
<div class="my-custom-dashboard-card">
    <h2>Media Library</h2>

    {{-- Clean standalone media manager component --}}
    <x-mediaflow-manager />
</div>
```

*(Note: The alias `<x-mediaflow::manager />` is also supported.)*

### Clean & Embedded Route Modes
The built-in route `/mediaflow/manager` runs in clean mode with developer navigation hidden by default.

* **Headerless / Clean Mode** (perfect for iframes or modals):
  ```text
  /mediaflow/manager?clean=1
  ```
* **Pure Grid Mode** (omits top uploader dropzone, shows only folders & files):
  ```text
  /mediaflow/manager?no_uploader=1
  ```

---

## PHP Developer API (`MediaFlow` Facade)

```php
use MediaFlow\Laravel\Facades\MediaFlow;

// 1. Upload a file from HTTP Request
$media = MediaFlow::upload($request->file('photo'), [
    'disk' => 'public',
    'folder' => 'avatars',
    'collection' => 'profiles',
    'thumbnails' => true,
]);

// 2. Access resolved delivery URLs and WebP thumbnail presets
$fullUrl = $media->url();
$thumbUrl = $media->thumbnailUrl('thumb');   // 200x200 crop WebP
$mediumUrl = $media->thumbnailUrl('medium'); // 800x800 contain WebP

// 3. Create folder hierarchies
$folder = MediaFlow::createFolder('Campaigns', $parentFolderId = null);

// 4. Move, Rename, Delete
MediaFlow::move($media, $folder->id);
MediaFlow::rename($media, 'hero-banner.webp');
MediaFlow::delete($media); // Deletes record, physical storage objects & variants

// 5. On-demand optimization (format: webp|jpeg|png, action: replace|copy)
$result = MediaFlow::optimize($media, ['format' => 'webp', 'quality' => 80, 'action' => 'copy']);
$savings = $result['savings_percent']; // e.g. 94.6
$copy = $result['media'];
```

### Eloquent Model Relationship

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaFlow\Laravel\Models\Media;

class Post extends Model
{
    protected $fillable = ['title', 'cover_media_id'];

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }
}
```

---

## Coexisting with Existing Media Models & Tables (Spatie MediaLibrary / Legacy Media)

If your Laravel application already has an existing `media` database table or an `App\Models\Media` model (such as from **Spatie MediaLibrary** or a legacy in-house upload system), MediaFlow is engineered to coexist with zero collisions:

### 1. Fully Isolated Namespace
MediaFlow's models live strictly inside `MediaFlow\Laravel\Models\Media`. Your application's `App\Models\Media` or `Spatie\MediaLibrary\MediaCollections\Models\Media` are completely untouched. You can import MediaFlow's model using an alias:

```php
use MediaFlow\Laravel\Models\Media as MediaFlowItem;
use MediaFlow\Laravel\Facades\MediaFlow;

// In your Post / Article model:
public function coverMedia()
{
    return $this->belongsTo(MediaFlowItem::class, 'cover_media_id');
}
```

### 2. Configurable Table Prefix & Names via `.env`
By default, MediaFlow tables are safely isolated with the `mediaflow_` prefix (`mediaflow_media`, `mediaflow_folders`, `mediaflow_variants`, `mediaflow_upload_sessions`, `mediaflow_settings`).

You can customize the prefix or individual table names in `.env`:
```env
# Change the prefix for all MediaFlow tables (default: mediaflow_)
MEDIAFLOW_TABLE_PREFIX=mediaflow_

# Or override specific table names if needed:
# MEDIAFLOW_TABLE_MEDIA=mediaflow_media
# MEDIAFLOW_TABLE_FOLDERS=mediaflow_folders
# MEDIAFLOW_TABLE_VARIANTS=mediaflow_variants
# MEDIAFLOW_TABLE_UPLOAD_SESSIONS=mediaflow_upload_sessions
# MEDIAFLOW_TABLE_SETTINGS=mediaflow_settings
```

### 3. Form Validation
Validate foreign keys to MediaFlow items dynamically:
```php
$request->validate([
    'cover_media_id' => 'required|exists:' . config('mediaflow.tables.media', 'mediaflow_media') . ',id',
]);
```

---

## Access Control, Guards & Roles

Enforce authentication and role restrictions via `config/mediaflow.php`:

```php
'auth' => [
    'enabled' => env('MEDIAFLOW_AUTH_ENABLED', true),   // on by default
    'guard' => env('MEDIAFLOW_GUARD', null),           // 'web', 'admin', 'sanctum' (MEDIAFLOW_AUTH_GUARD also accepted)
    'roles' => ['admin', 'editor'],                    // any one role: Spatie hasAnyRole/hasRole, $user->role, ...
    'permissions' => ['manage-media'],                 // any one permission: hasPermissionTo / can
    'gate' => env('MEDIAFLOW_GATE'),                   // optional Gate ability
    'custom_middleware' => [],                         // e.g. ['verified', 'throttle:60']
    'allow_ui_override' => false,                      // see note below
],
```

Settings saved on `/mediaflow/settings` can add restrictions but, by default, can never switch off authentication or replace roles defined in config/`.env`. Set `MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true` to let the Settings page fully control access.

### Per-User Media Scoping
Set `MEDIAFLOW_USER_SCOPED=true` so each logged-in user only sees, moves and deletes media and folders they own (`owner_id`). When a form submits a media ID, load it with `Media::findOrFail($id)` so the same scope applies.

### Custom Closure Authorization
Define custom access rules in `AppServiceProvider`:

```php
use MediaFlow\Laravel\Facades\MediaFlow;

public function boot(): void
{
    // Receives the resolved user (null for guests) and the request
    MediaFlow::authorize(function ($user, $request) {
        return $user?->isSuperAdmin() || $user?->can('access-media');
    });
}
```

---

## Cloud Storage & CDN Setup

### Amazon S3, Cloudflare R2, MinIO
Configure your storage disk in `.env`:

```env
MEDIAFLOW_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-media-bucket

# Cloudflare R2 via MediaFlow Cloud Provider (Recommended):
MEDIAFLOW_DISK=mediaflow_cloud
MEDIAFLOW_PROVIDER=cloudflare_r2
MEDIAFLOW_PROVIDER_ACCOUNT_ID=your-32-character-account-id
# (or CLOUDFLARE_ACCOUNT_ID=your-32-character-account-id)
MEDIAFLOW_PROVIDER_KEY=your-r2-access-key-id
MEDIAFLOW_PROVIDER_SECRET=your-r2-secret-access-key
MEDIAFLOW_PROVIDER_BUCKET=your-bucket-name
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://pub-xxxx.r2.dev

# Or standard Laravel S3 disk configuration for Cloudflare R2:
AWS_ACCESS_KEY_ID=your-r2-access-key-id
AWS_SECRET_ACCESS_KEY=your-r2-secret-access-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=https://your-32-char-account-id.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### CDN Domain Edge Resolver
```env
MEDIAFLOW_DELIVERY_DRIVER=cdn
MEDIAFLOW_CDN_URL=https://media-cdn.yourdomain.com
```

### Time-Limited Signed Private URLs
```php
$media = MediaFlow::upload($file, ['visibility' => 'private']);
$privateUrl = $media->temporaryUrl(30);       // minutes; S3 pre-signed URL or signed /mediaflow/delivery stream
$thumbUrl = $media->thumbnailUrl('thumb');    // also signed for private media
```

Set `MEDIAFLOW_PRIVATE_DISK=local` (or a private bucket) so private uploads never land on the public disk.

---

## Built-In Web Routes

| Route Name | Path | Description |
|---|---|---|
| `mediaflow.docs` | `/mediaflow/docs` | Interactive Documentation & Code Guide Portal |
| `mediaflow.manager` | `/mediaflow/manager` | Explorer & Media Library Browser |
| `mediaflow.pipeline` | `/mediaflow/pipeline` | Interactive 3D Upload Lifecycle Visualizer |
| `mediaflow.settings` | `/mediaflow/settings` | Configuration Dashboard & Compression Settings |
| `mediaflow.diagnostics.view` | `/mediaflow/diagnostics` | Storage Connection & Latency Health Probes |

---

## Running Automated Tests

```bash
composer install
./vendor/bin/phpunit
```

All **97 tests** (500 assertions) pass across PHP 8.2, PHP 8.3, and PHP 8.4, with and without the optional S3 driver.

---

## Events & Artisan Commands

| Event | Fired when |
|---|---|
| `MediaFlow\Laravel\Events\MediaReady` | Upload stored and variants generated |
| `MediaFlow\Laravel\Events\MediaFailed` | Upload pipeline failed (`$event->errorMessage`) |
| `MediaFlow\Laravel\Events\MediaDeleted` | Media deleted |

Browser components dispatch `mediaflow:uploaded` and `mediaflow:folder-changed` DOM events.

```php
// routes/console.php
Schedule::command('mediaflow:clean-temp')->hourly();       // purge expired temporary uploads
Schedule::command('mediaflow:verify-storage')->weekly();   // report records whose file is missing
```

---

## Security Checklist

- Keep `MEDIAFLOW_AUTH_ENABLED=true` and limit access with roles/permissions/Gate — the Settings and Diagnostics pages can change storage credentials.
- Use `MEDIAFLOW_PRIVATE_DISK` for private files and `MEDIAFLOW_USER_SCOPED=true` for multi-user libraries.
- Add rate limiting with `MEDIAFLOW_CUSTOM_MIDDLEWARE=throttle:60`.
- Keep `allowed_mimes` narrow and `sanitize_svg` enabled.
- Prefer `.env` for cloud credentials; secrets saved from the UI live in the `mediaflow_settings` table (masked in the UI and API).
- Re-publish assets after each update: `php artisan vendor:publish --tag=mediaflow-assets --force`.

See [CHANGELOG.md](CHANGELOG.md) for upgrade notes.

---

## Author & Maintainer

Developed and maintained by **Anuj Dwivedi**:

- **GitHub**: [@dwivedianuj9118](https://github.com/dwivedianuj9118)
- **LinkedIn**: [Anuj Dwivedi](https://www.linkedin.com/in/dwivedianuj9118/)
- **Email**: [dwivedianuj9118@gmail.com](mailto:dwivedianuj9118@gmail.com)
- **Website**: [anujdwivedi.online](https://anujdwivedi.online)

---

## License

The MediaFlow package is open-sourced software licensed under the [MIT license](LICENSE).

