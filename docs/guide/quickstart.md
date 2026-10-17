---
title: Quickstart
description: Add a MediaFlow upload field to a Laravel form, validate the submitted media ID, store it on your model and render the image in Blade.
---

# Quickstart

This walkthrough adds a cover image to an `Article` form. It assumes you have finished the [installation](/guide/installation).

## 1. Add the field to your form

```blade
{{-- resources/views/articles/create.blade.php --}}
<form method="POST" action="{{ route('articles.store') }}">
    @csrf

    <label for="title">Title</label>
    <input id="title" type="text" name="title" value="{{ old('title') }}" required>

    <x-mediaflow-input
        name="cover_media_id"
        label="Cover image"
        help="Drop an image, browse your device or pick one from the library."
        :required="true"
    />

    <button type="submit">Publish</button>
</form>
```

When the user drops or picks a file, the component uploads it straight away and writes the new media ID into a hidden input:

```html
<input type="hidden" id="mf-input-cover_media_id" name="cover_media_id" value="42">
```

So your form submits a plain integer, not a file.

## 2. Validate and save in the controller

```php
namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'cover_media_id' => [
                'required',
                'integer',
                // Media uses soft deletes, so ignore deleted rows
                Rule::exists('mediaflow_media', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $article = Article::create($validated);

        return redirect()->route('articles.show', $article);
    }
}
```

If you changed the table prefix, use your table name in the `exists` rule (for example `mf_media`).

::: warning Check who may use the file
`exists` only proves the ID is real. If several users share the app, make sure the current user may attach that file. With [per-user scoping](/security/user-scoping) turned on, `Media::findOrFail($id)` already enforces ownership.
:::

## 3. Add the relationship to your model

Alias the MediaFlow model so it never clashes with an `App\Models\Media` you may already have:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaFlow\Laravel\Models\Media as MediaFlowMedia;

class Article extends Model
{
    protected $fillable = ['title', 'cover_media_id'];

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaFlowMedia::class, 'cover_media_id');
    }
}
```

Your migration only needs a nullable column for the ID:

```php
$table->foreignId('cover_media_id')->nullable()->constrained('mediaflow_media');
```

::: info Deleting media that is still in use
`Media` uses soft deletes, so deleting a file from the library marks the row as deleted and removes the stored file, and a database foreign key does not block it. Handle missing covers in your views (`@if ($article->cover)`), or listen for the `MediaDeleted` [event](/api/events) to clear references.
:::

## 4. Render the image

```blade
@if ($article->cover)
    {{-- Original file (or CDN / signed URL, depending on delivery settings) --}}
    <img src="{{ $article->cover->url() }}" alt="{{ $article->title }}">

    {{-- 200×200 WebP thumbnail --}}
    <img src="{{ $article->cover->thumbnailUrl('thumb') }}" alt="" loading="lazy">
@endif
```

## 5. Pre-fill the field when editing

Pass the related model or its ID to `value`, and the component shows the preview card with Replace and Remove buttons:

```blade
<x-mediaflow-input
    name="cover_media_id"
    label="Cover image"
    :value="$article->cover ?? old('cover_media_id')"
/>
```

## Public-facing forms

If the form is used by customers rather than admins, decide what the library picker may show:

```blade
{{-- Upload only: no "Browse library" button --}}
<x-mediaflow-input name="avatar_id" label="Profile photo" :upload-only="true" />

{{-- Picker locked to one folder and collection --}}
<x-mediaflow-input name="avatar_id" label="Profile photo"
    :frontend="true" folder="user-avatars" collection="avatars" />
```

Remember that the upload routes still require a signed-in user who passes your [access rules](/security/access-control).

## Where next

- All field options: [Input Field](/components/input)
- Image sizes and formats: [Image Processing](/config/images)
- Moving to S3 or R2: [Cloud Providers](/storage/cloud-providers)
