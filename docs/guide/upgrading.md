---
title: Upgrading
description: Update MediaFlow for Laravel to the latest version and review the behaviour changes in the current release.
---

# Upgrading

## Update command

```bash
composer update dwivedianuj9118/mediaflow-laravel
php artisan vendor:publish --tag=mediaflow-assets --force
php artisan optimize:clear
php artisan migrate
```

| Step | Why |
| --- | --- |
| `composer update` | Pulls the new PHP code. |
| `vendor:publish --tag=mediaflow-assets --force` | Overwrites the published CSS, JavaScript and diagrams so they match the PHP code. Skipping this is the most common cause of "old behaviour after updating". |
| `optimize:clear` | Drops cached config, routes and views. Run `php artisan config:cache` again afterwards in production. |
| `migrate` | Applies any new package migrations. |

If you published the views (`mediaflow-views`), compare your copies with the package after each update.

## Behaviour changes in the current release

The unreleased security and reliability release changes some defaults. Review these before deploying:

- **Authentication is required by default.** `MEDIAFLOW_AUTH_ENABLED` now defaults to `true`. Previously every management route, including settings with cloud credentials, was open to anonymous visitors. See [Access Control](/security/access-control).
- **Config is a floor for access rules.** Settings saved in the web UI can no longer switch authentication off or replace the roles, permissions, guard or gate set in config or `.env`. Set `MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true` to opt back in.
- **Stored extensions come from the detected MIME type.** `notes.html` containing plain text is stored as `.txt`. HTML, XML and more script extensions are blocked.
- **The settings endpoint validates input.** `POST /mediaflow/settings` ignores unknown keys and returns `422` for invalid values.
- **`storage_quota_mb` no longer overwrites the max upload size.** The two settings used different units.
- **`scope.user_scoped` is enforced.** It was documented earlier but did nothing; now it filters every media and folder query. See [Per-User Scoping](/security/user-scoping).
- **New `MEDIAFLOW_PRIVATE_DISK`.** Point it at a non-public disk so private uploads never land on the public disk.

The full list is in the [changelog](/reference/changelog).
