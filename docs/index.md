---
layout: home
title: "MediaFlow for Laravel: Media Manager & Upload Toolkit"
titleTemplate: false
description: MediaFlow is a media manager and upload toolkit for Laravel. Add drag-and-drop upload fields, a media library, WebP optimization, S3 and Cloudflare R2 storage, signed URLs and role-based access in minutes.

hero:
  name: MediaFlow for Laravel
  text: Media uploads and a media library for Laravel apps
  tagline: Drop-in Blade upload fields, automatic WebP optimization, cloud storage presets and secure delivery, with no front-end build step.
  actions:
    - theme: brand
      text: Get started
      link: /guide/installation
    - theme: alt
      text: Quickstart
      link: /guide/quickstart
    - theme: alt
      text: View on GitHub
      link: https://github.com/dwivedianuj9118/mediaflow-laravel

features:
  - title: One-line upload field
    details: "<code>&lt;x-mediaflow-input&gt;</code> gives any form drag-and-drop upload, a preview card and a library picker, and submits a media ID."
    link: /components/input
    linkText: Input field
  - title: Media library
    details: Folders, search, filters, bulk move and delete, and an inspector panel, as a page or an embeddable component.
    link: /components/manager
    linkText: Media manager
  - title: Image optimization
    details: Uploads are converted to WebP, downscaled, stripped of EXIF/GPS data and given thumbnail variants, all with GD.
    link: /config/images
    linkText: Image processing
  - title: Cloud storage presets
    details: Cloudflare R2, AWS S3, DigitalOcean Spaces, Wasabi, Backblaze B2, MinIO and more, with a live connection test.
    link: /storage/cloud-providers
    linkText: Cloud providers
  - title: Secure by default
    details: Authentication on every management route, roles, permissions, gates, per-user libraries and signed URLs for private files.
    link: /security/access-control
    linkText: Access control
  - title: Works with your app
    details: Isolated tables and namespace, so it runs next to Spatie MediaLibrary or your own media model without conflicts.
    link: /config/database
    linkText: Database tables
---

<div class="vp-doc" style="max-width: 760px; margin: 64px auto 0; padding: 0 24px;">

## Install in four commands

```bash
composer require dwivedianuj9118/mediaflow-laravel
php artisan vendor:publish --tag=mediaflow-config
php artisan vendor:publish --tag=mediaflow-assets
php artisan migrate && php artisan storage:link
```

Then add the field to a form:

```blade
<x-mediaflow-input name="cover_media_id" label="Cover image" />
```

Continue with the [installation guide](/guide/installation) or jump to the [quickstart](/guide/quickstart).

</div>
