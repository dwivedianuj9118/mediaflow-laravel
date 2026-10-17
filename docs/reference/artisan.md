---
title: Artisan Commands
description: MediaFlow's Artisan commands for cleaning temporary uploads and verifying stored files, publish tags and scheduling examples.
---

# Artisan Commands

| Command | Purpose |
| --- | --- |
| `php artisan mediaflow:clean-temp` | Delete expired temporary upload sessions and their files. Add `--dry-run` to only count them. |
| `php artisan mediaflow:verify-storage` | Check that every media record still has its file in storage. Prints a summary and exits with a failure code when files are missing, which makes it useful in CI or monitoring. Add `--disk=public` to check one disk. |

## Scheduling

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('mediaflow:clean-temp')->hourly();
Schedule::command('mediaflow:verify-storage')->weekly()->emailOutputOnFailure('ops@example.com');
```

## Publish tags

| Tag | Publishes |
| --- | --- |
| `mediaflow-config` | `config/mediaflow.php` |
| `mediaflow-assets` | `public/vendor/mediaflow/{css,js,img}` |
| `mediaflow-migrations` | Migrations into `database/migrations` |
| `mediaflow-views` | Blade views into `resources/views/vendor/mediaflow` |

```bash
php artisan vendor:publish --tag=mediaflow-assets --force
```

Re-publish the assets after every update so the CSS and JavaScript match the PHP code.
