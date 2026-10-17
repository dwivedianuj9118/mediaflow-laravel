# MediaFlow for Laravel — Complete Installation & Usage Guide

This guide provides end-to-end instructions for installing, configuring, and using **MediaFlow for Laravel** in any Laravel 11.x, 12.x, or 13.x project.

---

## 1. System Requirements

- **PHP**: `^8.2 | ^8.3`
  - Required extensions: `fileinfo`, `gd` (or `imagick`), `exif`
- **Laravel Framework**: `^11.0 | ^12.0 | ^13.0`
- **Database**: SQLite 3.35+, MySQL 8.0+, or PostgreSQL 14+
- **Composer**: 2.x

---

## 2. Installation Steps

### Step 1: Install Package via Composer
```bash
composer require mediaflow/mediaflow-laravel
```

### Step 2: Publish Package Config, Migrations & Assets
```bash
# Publish configuration (config/mediaflow.php)
php artisan vendor:publish --tag=mediaflow-config

# Publish migrations
php artisan vendor:publish --tag=mediaflow-migrations

# Publish native CSS/JS assets
php artisan vendor:publish --tag=mediaflow-assets
```

### Step 3: Run Database Migrations
```bash
php artisan migrate
```

### Step 4: Create Public Storage Symlink
```bash
php artisan storage:link
```

---

## 3. Environment Configuration (`.env`)

Add or customize the following variables in your `.env` file:

```env
# URL route prefix for media routes (default: mediaflow)
MEDIAFLOW_ROUTE_PREFIX=mediaflow

# Storage disk: 'public', 's3', etc.
MEDIAFLOW_DISK=public

# Max upload file size in KB (default: 20480 = 20 MB)
MEDIAFLOW_MAX_FILE_SIZE_KB=20480

# Delivery driver: 'disk' or 'cdn'
MEDIAFLOW_DELIVERY_DRIVER=disk
MEDIAFLOW_CDN_URL=https://cdn.example.com

# Access control & guards
MEDIAFLOW_AUTH_ENABLED=false
MEDIAFLOW_AUTH_GUARD=web
MEDIAFLOW_AUTH_ROLES=admin,editor
```

---

## 4. Blade UI Components

### 4.1 Shortcut Form Input: `<x-mediaflow-input>`
Use in any standard Blade form. Submits the selected `Media` ID as a standard POST parameter:

```blade
<form method="POST" action="/articles">
    @csrf

    <div class="form-group">
        <label>Article Title</label>
        <input type="text" name="title" class="form-control" required>
    </div>

    {{-- Drop-in Media Upload & Picker Box --}}
    <x-mediaflow-input 
        name="cover_media_id" 
        label="Featured Cover Image"
        help="Drag and drop or select an image from your library."
        :required="true"
    />

    <button type="submit">Publish</button>
</form>
```

#### Side Admin Panel Mode (`:compact="true"`)
When rendering inside sidebars, drawers, or narrow columns (280px–340px):

```blade
<x-mediaflow-input 
    name="sidebar_thumbnail_id" 
    label="Drawer Thumbnail"
    :compact="true"
    help="Scaled down for narrow sidebars."
/>
```

#### Pre-populating Existing Media on Edit
```blade
<x-mediaflow-input 
    name="cover_media_id" 
    label="Cover Image"
    :value="$post->coverMedia ?? old('cover_media_id')" 
/>
```

#### Supported Props

| Prop | Type | Default | Description |
|---|---|---|---|
| `name` | `string` | `'media_id'` | Form field name submitted in `POST` requests. |
| `label` | `string` | `null` | Optional label displayed above dropzone. |
| `value` | `mixed` | `null` | Initial media selection (`Media` model, ID integer, or URL). |
| `compact` | `bool` | `false` | Enable for admin sidebars, off-canvas drawers, and narrow columns. |
| `picker` | `bool` | `true` | Show or hide the "Browse Library" button. |
| `accept` | `string` | `'image/*'` | Allowed MIME type filter for the file selector. |
| `folder` | `string` | `null` | Target folder slug/UUID for uploads from this field. |
| `collection` | `string` | `'default'` | Target media collection name. |
| `required` | `bool` | `false` | Adds required asterisk indicator and validation flags. |
| `help` | `string` | `null` | Helper text displayed beneath the dropzone. |

---

### 4.2 Standalone Batch Uploader: `<x-mediaflow::uploader>`
```blade
<x-mediaflow::uploader
    folder="gallery"
    collection="product_shots"
    accept="image/*,application/pdf"
    :multiple="true"
    :animated="true"
/>
```

---

### 4.3 Explorer & Media Manager: `<x-mediaflow::manager>`
```blade
{{-- Standalone Finder / Explorer mode --}}
<x-mediaflow::manager mode="standalone" />

{{-- Picker mode --}}
<x-mediaflow::manager mode="picker" name="selected_media_id" />
```

---

## 5. Eloquent Model Integration

Define a `belongsTo` relationship on your models:

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

Render delivery URLs and WebP thumbnails in Blade:

```blade
<img src="{{ $post->coverMedia->url() }}" alt="{{ $post->title }}">

{{-- Presets: 'thumb' (200x200), 'medium' (800x800), 'large' (1600x1600) --}}
<img src="{{ $post->coverMedia->thumbnailUrl('thumb') }}" loading="lazy">
<img src="{{ $post->coverMedia->thumbnailUrl('medium') }}">
```

---

## 6. PHP Developer API (`MediaFlow` Facade)

```php
use MediaFlow\Laravel\Facades\MediaFlow;

// Upload from Request
$media = MediaFlow::upload($request->file('avatar'), [
    'disk' => 'public',
    'folder' => 'users/avatars',
    'collection' => 'avatars',
    'thumbnails' => true,
]);

// Folder operations
$folder = MediaFlow::createFolder('Products', $parentId = null);

// Move, Rename, Delete
MediaFlow::move($media, $folder->id);
MediaFlow::rename($media, 'new-photo.jpg');
MediaFlow::delete($media);

// On-demand WebP/AVIF optimization
$result = MediaFlow::optimize($media, 'webp', quality: 80, replaceOriginal: false);
```

---

## 7. Access Control, Guards & Roles

Enforce authentication in `config/mediaflow.php`:

```php
'auth' => [
    'enabled' => true,
    'guard' => 'web',               // 'web', 'admin', 'api'
    'roles' => ['admin', 'editor'], // Checks $user->role or Spatie hasRole()
    'permissions' => ['manage-media'],
],
```

Or register custom closure authorization in `AppServiceProvider`:

```php
MediaFlow::authorize(function ($request) {
    return $request->user()?->isSuperAdmin() || $request->user()?->can('access-media');
});
```

---

## 8. Built-In Web Routes

| Path | Name | Purpose |
|---|---|---|
| `/mediaflow/docs` | `mediaflow.docs` | Interactive Documentation & Code Guide Portal |
| `/mediaflow/manager` | `mediaflow.manager` | Explorer & Media Library Browser |
| `/mediaflow/pipeline` | `mediaflow.pipeline` | Interactive 3D Upload Lifecycle Visualizer |
| `/mediaflow/settings` | `mediaflow.settings` | Configuration Dashboard & Compression Settings |
| `/mediaflow/diagnostics` | `mediaflow.diagnostics.view` | Storage Connection & Latency Health Probes |

---

## 9. Testing

```bash
# Test package
cd packages/mediaflow-laravel
./vendor/bin/phpunit

# Test demo application
cd demo
php artisan test
```

---

## 10. Author & Maintainer

Created and maintained by **Anuj Dwivedi**:
- **GitHub**: [@dwivedianuj9118](https://github.com/dwivedianuj9118)
- **LinkedIn**: [Anuj Dwivedi](https://www.linkedin.com/in/dwivedianuj9118/)
- **Email**: [dwivedianuj9118@gmail.com](mailto:dwivedianuj9118@gmail.com)
- **Website**: [anujdwivedi.online](https://anujdwivedi.online)

