---
title: Uploader
description: Reference for the <x-mediaflow::uploader> Blade component for single or multi-file uploads with progress and previews.
---

# Uploader

`<x-mediaflow::uploader>` is a standalone drop zone for single or batch uploads, with a progress bar and a preview list. Each uploaded file adds a hidden input with its media ID, so it also works inside a form.

```blade
<x-mediaflow::uploader
    name="gallery_ids"
    folder="gallery/products"
    collection="product-shots"
    accept="image/*,application/pdf"
    :multiple="true"
/>
```

`<x-mediaflow::uploader>` and `<x-mediaflow-uploader>` are the same component.

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | string | `media_id` | Name of the hidden input. With `multiple`, inputs are named `name[]`. |
| `accept` | string | `image/*` | Types the file dialog offers. The server still applies the [upload rules](/config/uploads). |
| `multiple` | bool | `false` | Allow several files at once. |
| `animated` | bool | `true` | Animate progress and previews. |
| `folder` | string | `null` | Folder ID, name or path for uploads. |
| `collection` | string | `default` | Collection stored with each upload. |
| `visibility` | string | `public` | `public` or `private`. |
| `upload-url` | string | `route('mediaflow.upload')` | Custom upload endpoint. |

When an uploader is on the same page as a [media manager](/components/manager), it follows the folder the manager has open.

## Receiving the IDs

```php
$request->validate([
    'gallery_ids' => 'array',
    'gallery_ids.*' => 'integer',
]);
```

## Events

After every successful upload the uploader dispatches `mediaflow:uploaded` with `{ media }` on its element (bubbling) and on `document`:

```js
document.addEventListener('mediaflow:uploaded', (e) => {
  console.log(e.detail.media.url)
})
```
