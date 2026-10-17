---
title: Events
description: Laravel events (MediaReady, MediaFailed, MediaDeleted) and browser DOM events fired by MediaFlow components, plus the window.MediaFlow JavaScript helpers.
---

# Events

## Laravel events

| Event | Properties | Fired when |
| --- | --- | --- |
| `MediaFlow\Laravel\Events\MediaReady` | `$event->media` | The original and its variants are stored and the status is `ready`. |
| `MediaFlow\Laravel\Events\MediaFailed` | `$event->media`, `$event->errorMessage` | Storing or processing failed. Files already written are cleaned up. |
| `MediaFlow\Laravel\Events\MediaDeleted` | `$event->media` | A media item was deleted through the UI, the API or `MediaFlow::delete()`. |

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Event;
use MediaFlow\Laravel\Events\MediaDeleted;
use MediaFlow\Laravel\Events\MediaReady;

public function boot(): void
{
    Event::listen(MediaReady::class, function (MediaReady $event) {
        logger()->info('Media ready', ['id' => $event->media->id]);
    });

    // Clear references to deleted files
    Event::listen(MediaDeleted::class, function (MediaDeleted $event) {
        \App\Models\Post::where('cover_media_id', $event->media->id)
            ->update(['cover_media_id' => null]);
    });
}
```

## Browser events

All events are `CustomEvent`s. Component events bubble, so you can listen on `document` or on a wrapper.

| Event | Dispatched by | `event.detail` |
| --- | --- | --- |
| `mediaflow:uploaded` | Uploader (on the element and on `document`) | `{ media }` |
| `mediaflow:change` | Input field | `{ media }` after an upload or pick |
| `mediaflow:removed` | Input field | none |
| `mediaflow:picked` | Manager in `picker` mode | `{ item, items }` |
| `mediaflow:folder-changed` | Manager (on `document`) | `{ folderId, folderName }` |

```js
document.addEventListener('mediaflow:change', (e) => {
  const media = e.detail.media
  console.log(media.id, media.url)
})

document.addEventListener('mediaflow:picked', (e) => {
  console.log(e.detail.items.map((m) => m.id))
})
```

::: info Renamed event
Older docs mentioned a `mediaflow:selected` event. It is not dispatched; use `mediaflow:change` for input fields and `mediaflow:picked` for the manager.
:::

## JavaScript helpers

`mediaflow.js` exposes `window.MediaFlow`:

| Helper | Use |
| --- | --- |
| `MediaFlow.toast(message, type)` | Show a toast notification (`showToast` is an alias). |
| `MediaFlow.copyToClipboard(text)` | Copy text and confirm with a toast. |
| `MediaFlow.initUploader(el)`, `initInput(el)`, `initManager(el)` | Initialise components added to the page after load, for example by Livewire or Turbo. |
| `MediaFlow.openPickerModal(options)` | Open the library picker. |

Components present when the page loads are initialised automatically.
