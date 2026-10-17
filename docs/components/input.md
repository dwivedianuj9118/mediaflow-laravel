---
title: Input Field
description: Reference for the <x-mediaflow-input> Blade component, a drop-in upload field with drag-and-drop, preview, library picker and file type presets.
---

# Input Field

`<x-mediaflow-input>` turns any form field into an upload box. It supports drag-and-drop and file browsing, uploads immediately, shows a preview card with Open, Copy URL, Replace and Remove buttons, and offers a picker to choose an existing file from the library. The form submits the **media ID** in a hidden input.

```blade
<x-mediaflow-input
    name="cover_media_id"
    label="Cover image"
    help="Drag and drop an image or pick one from the library."
    :required="true"
/>
```

`<x-mediaflow-input>` and `<x-mediaflow::input>` are the same component.

## Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | string | required | Name of the hidden input that carries the media ID. |
| `label` | string | `null` | Label above the field. |
| `help` | string | `null` | Helper text below the field. |
| `required` | bool | `false` | Shows the required marker and sets `required` on the field. |
| `value` | Media, int or string | `null` | Initial value: a `Media` model, a media ID or a URL. Shows the preview card. |
| `type` | string | `image` | File preset: `image`, `video`, `audio`, `doc` / `docs` / `pdf`, or `any` / `*`. Sets the accepted types, icon, helper text and picker filter. `types` is an alias. |
| `accept` | string | from `type` | Custom accept list, e.g. `.pdf,.docx` or `video/*`. |
| `folder` | string | `null` | Folder ID, name or path (e.g. `blog/covers`) for new uploads. Missing folders are created. |
| `collection` | string | `default` | Collection name stored with each upload. |
| `visibility` | string | `public` | `public` or `private`. Private files get [signed URLs](/storage/delivery). |
| `compact` | bool | `false` | Smaller layout for sidebars and drawers (about 280–340 px wide). |
| `picker` | bool | `true` | Show the "Browse library" button. |
| `upload-only` | bool | `false` | Hide the library picker. `:browse="false"` and `:hide-browse="true"` do the same. |
| `frontend` | bool | `false` | Lock the picker to `folder` and `collection` and hide admin folders. `:isolated="true"` is an alias. |
| `picker-scope` | string | `all`, or `frontend` when `frontend` is on | Scope passed to the picker. |
| `open-tab` | bool | `true` | Show the Open button on the preview card. |
| `copy-url` | bool | `true` | Show the Copy URL button on the preview card. |
| `pipeline` | bool | `ui.show_pipeline` (on) | Show the animated upload pipeline while uploading. `visualizer` is an alias. |
| `upload-url` | string | `route('mediaflow.upload')` | Send uploads to a different endpoint. |
| `theme` | string | settings | Accent preset for this field; see [Theming](/components/theming). |
| `mode` | string | settings | `light`, `dark` or `system` for this field. |
| `folder-color` | string | settings | Hex colour for folder icons in the picker. |

Any other attributes, such as `class` or `id`, are added to the wrapper element.

## File types

```blade
{{-- Video: MP4, WebM, Ogg, QuickTime --}}
<x-mediaflow-input name="trailer_id" label="Trailer" type="video" folder="videos" />

{{-- Documents: PDF, Word, Excel, PowerPoint, text, CSV --}}
<x-mediaflow-input name="contract_id" label="Signed contract" type="docs" accept=".pdf,.docx" />

{{-- Audio: MP3, WAV, Ogg --}}
<x-mediaflow-input name="episode_id" label="Episode audio" type="audio" folder="podcasts" />

{{-- Anything allowed by the server --}}
<x-mediaflow-input name="attachment_id" label="Attachment" type="any" />
```

The `accept` list only filters what the browser offers. The server still applies the [upload rules](/config/uploads), so make sure the types you want are in `allowed_mimes`.

## Editing existing records

```blade
<x-mediaflow-input
    name="cover_media_id"
    label="Cover image"
    :value="$post->cover ?? old('cover_media_id')"
/>
```

## Public forms

For forms used by customers or guests, keep them away from the admin library:

```blade
{{-- Upload only --}}
<x-mediaflow-input name="avatar_id" label="Profile photo" :upload-only="true" />

{{-- Picker locked to one folder and collection --}}
<x-mediaflow-input name="avatar_id" label="Profile photo"
    :frontend="true" folder="user-avatars" collection="avatars" />
```

For real separation between users, also turn on [per-user scoping](/security/user-scoping). Picker restrictions are a convenience in the UI; scoping is enforced on the server.

## Sidebars and drawers

Use `compact` in narrow panels:

```blade
<aside style="width: 320px">
    <x-mediaflow-input name="thumbnail_id" label="Thumbnail" :compact="true" />
</aside>
```

The picker and pipeline modals are attached to `document.body` with a very high `z-index`, so `overflow: hidden`, `transform` or `filter` on a parent panel cannot clip them.

## Browser events

The field dispatches bubbling DOM events you can listen to:

| Event | `event.detail` | When |
| --- | --- | --- |
| `mediaflow:change` | `{ media }` | A file was uploaded or picked and the field now has a value. |
| `mediaflow:removed` | none | The user removed the current file. |

```js
document.addEventListener('mediaflow:change', (e) => {
  console.log('New media ID', e.detail.media.id)
})
```

See [Events](/api/events) for all PHP and browser events.
