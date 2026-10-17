---
title: Per-User Scoping
description: Give every Laravel user a private MediaFlow library with owner-based global scopes on media and folders.
---

# Per-User Scoping

Turn on scoping to give every user their own private library:

```dotenv
MEDIAFLOW_USER_SCOPED=true
```

A global Eloquent scope, `MediaFlow\Laravel\Models\Scopes\OwnerScope`, then limits every `Media` and `MediaFolder` query to rows whose `owner_id` matches the signed-in user. Listing, moving, optimizing and deleting someone else's file returns `404`.

New uploads and folders record the current user as owner automatically. Pass `owner_id` to `MediaFlow::upload()` or `createFolder()` to set it yourself.

## What is not filtered

The scope only applies while a user is authenticated. These are **not** filtered:

- console commands and queued jobs
- signed delivery URLs, which are already limited by their signature

## Bypassing the scope

Admin tools can read everything explicitly:

```php
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\Scopes\OwnerScope;

Media::withoutGlobalScope(OwnerScope::class)->latest()->get();
```

## Validating submitted IDs

When a form submits a media ID, make sure the user may use it. With scoping on, fetching through the model enforces ownership:

```php
$media = Media::findOrFail($request->integer('cover_media_id')); // 404 for other users' files
```

The plain `exists:mediaflow_media,id` validation rule queries the table directly and does **not** apply the scope.

::: info Tenants
`tenant_id` is stored with every record, but `scope.tenant_column` is reserved and tenant filtering is not enforced yet.
:::
