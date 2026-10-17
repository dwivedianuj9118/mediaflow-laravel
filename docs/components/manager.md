---
title: Media Manager
description: Embed the MediaFlow media library in Laravel with <x-mediaflow::manager>, or use the built-in manager page with folders, search, bulk actions and a picker mode.
---

# Media Manager

The media manager is a full library UI: a folder tree, search, type filters, a grid with hover actions, an inspector panel, rename and move, bulk delete and move, and uploads into the open folder.

## Built-in page

Visit `/mediaflow/manager` (route `mediaflow.manager`). `/mediaflow` opens the same page.

| Query parameter | Effect |
| --- | --- |
| `?clean=1` or `?embed=1` | Hide the header, for iframes and modals. |
| `?no_uploader=1` | Hide the upload area and show only folders and files. |
| `?folder_id=12` | Open a specific folder. |

The header can be turned off everywhere with `MEDIAFLOW_SHOW_HEADER=false`. Links to the docs and pipeline pages appear in it only when `MEDIAFLOW_DEV_NAV=true`.

## Embedding the component

```blade
<x-mediaflow::manager />
```

`<x-mediaflow::manager>` and `<x-mediaflow-manager>` are the same component.

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `mode` | string | `standalone` | `standalone` for a library, or `picker` to add a **Select media** button. |
| `multiple` | bool | `false` | Allow selecting several items in picker mode. |
| `name` | string | `selected_media_id` | Input name used in picker mode. |
| `folder-id` | int | `null` | Folder to open first. `folder` is an alias. |
| `collection` | string | `null` | Limit the library to one collection. |
| `pipeline` | bool | `ui.show_pipeline` (on) | Show the pipeline animation for uploads. `visualizer` is an alias. |
| `theme` | string | settings | Accent preset; see [Theming](/components/theming). |
| `color-mode` | string | settings | `light`, `dark` or `system`. |
| `folder-color` | string | settings | Hex colour for folder icons. |

## Picker mode

```blade
<x-mediaflow::manager mode="picker" :multiple="true" />
```

When the user clicks **Select media**, the component dispatches `mediaflow:picked`:

```js
document.addEventListener('mediaflow:picked', (e) => {
  const { item, items } = e.detail // first selection and all selections
  console.log(items.map((m) => m.id))
})
```

## Folder changes

Opening a folder dispatches `mediaflow:folder-changed` on `document` with `{ folderId, folderName }`. Uploaders on the same page use it to upload into the open folder.

## Folder rules

- Folder names are unique within their parent.
- Renaming or moving a folder updates its slug and the paths of all descendants.
- A folder cannot be moved into its own subtree.
- Deleting a folder moves its files and subfolders up to the parent.

## Who can do what

Everyone who passes [access control](/security/access-control) can upload, rename, move and delete. The `ui.allow_delete`, `ui.allow_rename` and `ui.allow_move` keys in `config/mediaflow.php` are reserved and not enforced yet, so restrict destructive actions with roles, a gate or `MediaFlow::authorize()`.
