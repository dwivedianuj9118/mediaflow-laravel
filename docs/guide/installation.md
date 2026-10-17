---
title: Installation
description: Install MediaFlow for Laravel with Composer, publish its config and assets, run the migrations and add the CSS and JavaScript to your layout.
---

# Installation

## 1. Require the package

```bash
composer require dwivedianuj9118/mediaflow-laravel
```

The service provider and the `MediaFlow` facade are registered automatically.

## 2. Publish the config and assets

```bash
# config/mediaflow.php
php artisan vendor:publish --tag=mediaflow-config

# CSS, JavaScript and diagrams to public/vendor/mediaflow
php artisan vendor:publish --tag=mediaflow-assets
```

| Tag | Publishes | When to use it |
| --- | --- | --- |
| `mediaflow-config` | `config/mediaflow.php` | Always, so you can review the defaults. |
| `mediaflow-assets` | `public/vendor/mediaflow/{css,js,img}` | Always. Re-run with `--force` after every update. |
| `mediaflow-migrations` | The migration files into `database/migrations` | Only if you want to edit the migrations. They run from the package otherwise. |
| `mediaflow-views` | Blade views into `resources/views/vendor/mediaflow` | Only if you want to customise the components or pages. |

::: tip Assets without publishing
If the assets are not published yet, MediaFlow serves its CSS and JavaScript from the `/vendor/mediaflow/...` routes as a fallback. Publishing them is still recommended so your web server can serve the files directly.
:::

## 3. Run the migrations

```bash
php artisan migrate
```

This creates five tables: `mediaflow_media`, `mediaflow_folders`, `mediaflow_variants`, `mediaflow_upload_sessions` and `mediaflow_settings`. To use a different prefix, set `MEDIAFLOW_TABLE_PREFIX` **before** migrating; see [Database Tables](/config/database).

## 4. Link public storage

```bash
php artisan storage:link
```

Files on the `public` disk are served through this symlink.

## 5. Add the assets to your layout

Add the CSRF meta tag, the stylesheet and the script to your main layout, for example `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Application</title>

    {{-- Required: the components send this token with every upload --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @mediaflowStyles
</head>
<body>
    @yield('content')

    @mediaflowScripts
</body>
</html>
```

`@mediaflowStyles` and `@mediaflowScripts` print the `<link>` and `<script defer>` tags once per page, even if several components are rendered. You can write the tags yourself instead:

```blade
<link rel="stylesheet" href="{{ asset('vendor/mediaflow/css/mediaflow.css') }}">
<script src="{{ asset('vendor/mediaflow/js/mediaflow.js') }}" defer></script>
```

## 6. Sign in and open the Settings page

Authentication is **on by default**, so sign in to your app first, then visit:

```
https://your-app.test/mediaflow/settings
```

From there you can test a storage connection, choose the default image format and quality, and pick a theme. See [Settings Page](/config/settings-page).

::: warning Local demos without login
For a quick local demo you can set `MEDIAFLOW_AUTH_ENABLED=false`. Never do this in production: anyone could upload or delete files and read or change your storage settings.
:::

## 7. Recommended `.env` for production

```dotenv
MEDIAFLOW_ROUTE_PREFIX=admin/media
MEDIAFLOW_AUTH_ENABLED=true
MEDIAFLOW_ROLES=admin,editor

MEDIAFLOW_DISK=public
MEDIAFLOW_PRIVATE_DISK=local
MEDIAFLOW_MAX_FILE_SIZE_KB=20480
```

Run `php artisan config:cache` after installing so the cached configuration includes MediaFlow. Every option is listed in [Environment Variables](/config/environment).

## Next

Follow the [quickstart](/guide/quickstart) to add an upload field to a form and save the result.
