---
title: Testing & Troubleshooting
description: Run MediaFlow's test suite, test uploads in your own Laravel feature tests, and fix common problems such as 401, 419, 413 and missing thumbnails.
---

# Testing & Troubleshooting

## Running the package tests

```bash
composer install
vendor/bin/phpunit
```

The suite uses Orchestra Testbench and an in-memory SQLite database. CI runs it on PHP 8.2, 8.3 and 8.4.

## Testing uploads in your app

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

public function test_admin_can_upload_a_cover(): void
{
    Storage::fake('public');

    $this->actingAs($this->admin())
        ->postJson(route('mediaflow.upload'), [
            'file' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
        ])
        ->assertOk()
        ->assertJsonPath('success', true);
}
```

`UploadedFile::fake()->image()` needs the GD extension.

## Common problems

| Symptom | Fix |
| --- | --- |
| `401 Unauthenticated` or a redirect to login | Sign in, or for a local demo only set `MEDIAFLOW_AUTH_ENABLED=false`. |
| `403` for a signed-in user | The user fails a role, permission, gate or callback check. See [Access Control](/security/access-control). |
| `419 CSRF token mismatch` | Add `<meta name="csrf-token" content="{{ csrf_token() }}">` to your layout. The components also read `data-csrf-token` and can refresh the token from `/mediaflow/csrf-token`. |
| Images return 404 on the public disk | Run `php artisan storage:link` and check `APP_URL`. |
| No thumbnails | Enable the PHP `gd` extension with WebP support. Check the [Diagnostics page](/reference/diagnostics). |
| `MIME type [...] is not allowed` | Add the detected type to `allowed_mimes`, e.g. `image/*`. |
| `413` or an empty upload | Raise `upload_max_filesize`, `post_max_size` and your web server's body limit. |
| "Cloud storage needs the S3 driver" | Run `composer require league/flysystem-aws-s3-v3 "^3.0"`. It is normally installed with MediaFlow. |
| Any file type accepted, or no thumbnails, after a deploy | Your config cache predates MediaFlow. Run `php artisan config:cache`. MediaFlow loads safe defaults and logs a warning until you do. |
| Old UI behaviour after updating | Re-publish assets: `php artisan vendor:publish --tag=mediaflow-assets --force`. |
| Picker or modal cut off inside a sidebar | Make sure `mediaflow.js` is loaded; modals are attached to `document.body` so parents cannot clip them. |
| Components added by Livewire or Turbo do nothing | Call `window.MediaFlow.initInput(el)`, `initUploader(el)` or `initManager(el)` after inserting them. |
