---
title: Settings Page
description: Use the MediaFlow Settings page and settings API to change image quality, storage, delivery, theme and access rules at runtime without redeploying.
---

# Settings Page

MediaFlow ships an admin page at `/mediaflow/settings` (route `mediaflow.settings`). Values saved there are stored in the `mediaflow_settings` table and cached, so you can change image quality, storage, delivery and appearance without a deploy.

What you can change:

- **Images:** default format, quality, max width and height, auto-optimize, EXIF stripping.
- **Uploads and storage:** max file size, default disk.
- **Cloud provider:** preset, credentials, bucket, region, endpoint, public URL, with a **Test connection** button.
- **Delivery:** driver, CDN base URL, signed URL lifetime.
- **Appearance:** theme, light/dark mode, custom accent and folder colour, animations.
- **Access:** auth on/off, guard, roles, permissions, gate (limited by config, see below).

## Saved settings vs config

For most options, a saved value wins over `config/mediaflow.php`. Access control is the exception: by default, config and `.env` are a **floor** that the page cannot weaken.

- Saving "auth disabled" never overrides `MEDIAFLOW_AUTH_ENABLED=true`.
- Roles, permissions, guard and gate from config win over saved ones.

Set `MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true` if you want the page to be the single source of truth.

The cloud secret is never sent back to the browser. The page shows a masked placeholder, and saving the placeholder keeps the stored secret.

::: warning Who can open this page
The Settings page can change storage credentials. Limit MediaFlow to administrators with [roles, permissions or a gate](/security/access-control).
:::

## Settings API

The same page answers JSON requests, so SPAs and custom admin panels can read and write settings.

```js
const token = document.querySelector('meta[name="csrf-token"]').content

// Read (secrets masked) plus the provider list
const current = await fetch('/mediaflow/settings', {
  headers: { Accept: 'application/json' },
}).then((r) => r.json())

// Update
const res = await fetch('/mediaflow/settings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': token,
  },
  body: JSON.stringify({
    'images.default_format': 'webp',
    'images.default_quality': 85,
    'images.max_width': 2560,
    'ui.theme': 'slate',
    'ui.mode': 'light',
  }),
})
```

Unknown keys are ignored. Invalid values return `422` with the reason.

### Accepted keys

| Key | Accepted values |
| --- | --- |
| `images.default_format` | `webp`, `jpeg`, `png`, `keep` |
| `images.default_quality` | integer 30–100 |
| `images.max_width`, `images.max_height` | integer 20–20000 |
| `images.auto_optimize`, `images.strip_exif` | boolean |
| `upload.max_file_size_kb` | integer, KB |
| `storage.default_disk` | a disk defined in `config/filesystems.php` |
| `storage_quota_mb` | integer, MB (informational; does not change the upload limit) |
| `delivery.driver` | `disk`, `cdn`, `signed` |
| `delivery.base_url` | URL |
| `delivery.signed_url_expiration_minutes` | integer 1–10080 |
| `ui.theme` | `system`, `indigo`, `emerald`, `violet`, `sky`, `amber`, `rose`, `slate`, `custom` |
| `ui.mode` | `system`, `light`, `dark` |
| `ui.custom_primary`, `ui.folder_color` | hex colour, e.g. `#2563eb` |
| `ui.inherit_system`, `ui.animations` | boolean |
| `auth.enabled` | boolean |
| `auth.guard`, `auth.gate` | name |
| `auth.roles`, `auth.permissions` | list or comma-separated string |
| `provider.name` | a [provider preset](/storage/cloud-providers) ID or `local` |
| `provider.key`, `provider.secret`, `provider.account_id`, `provider.bucket`, `provider.region` | string |
| `provider.endpoint`, `provider.public_url` | URL |
| `provider.path_style` | boolean |

Short aliases are also accepted, for example `theme`, `mode`, `folder_color`, `image_quality`, `default_image_format`, `auth_roles` and `provider_key`.

### Other endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `POST` | `/mediaflow/settings/reset` | Delete all saved settings and fall back to config. |
| `POST` | `/mediaflow/settings/test-provider` | Test cloud credentials; see [Cloud Providers](/storage/cloud-providers#testing-a-connection). |
| `GET` | `/mediaflow/settings/providers` | Metadata for the ten provider presets. |

If the cache store fails, settings are read from the database directly, so the UI keeps working.
