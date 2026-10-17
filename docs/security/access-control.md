---
title: Access Control
description: Protect MediaFlow's Laravel routes with authentication, guards, Spatie roles and permissions, gates, custom middleware and an authorization callback.
---

# Access Control

Every MediaFlow page and endpoint runs through the `AuthorizeMediaFlowAccess` middleware. **Authentication is required by default.**

Always reachable without login:

- the in-app documentation page (`/mediaflow/docs`)
- the CSRF token endpoint (`/mediaflow/csrf-token`)
- signed delivery URLs (`/mediaflow/delivery/{media}`), which check their signature instead

Guests get `401` with a JSON body, or are redirected to your `login` route for normal page requests. Users who fail a check get `403`.

## Configuration

```php
// config/mediaflow.php
'auth' => [
    'enabled' => env('MEDIAFLOW_AUTH_ENABLED', true),
    'guard' => env('MEDIAFLOW_GUARD', env('MEDIAFLOW_AUTH_GUARD')),   // null = default guard
    'roles' => [],               // MEDIAFLOW_ROLES=admin,editor
    'permissions' => [],         // MEDIAFLOW_PERMISSIONS=manage-media
    'gate' => env('MEDIAFLOW_GATE'),
    'custom_middleware' => [],   // MEDIAFLOW_CUSTOM_MIDDLEWARE=verified,throttle:60,1
    'allow_ui_override' => env('MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE', false),
],
```

## How a request is checked

The checks run in this order, and each one that is configured must pass:

1. **Authentication** with the configured guard.
2. **Authorization callback**, if you registered one with `MediaFlow::authorize()`.
3. **Roles:** the user needs any one of them.
4. **Permissions:** the user needs any one of them.
5. **Gate:** `Gate::forUser($user)->allows($gate)` must be true.

Roles are detected in several ways, so most role systems work without glue code:

- Spatie `hasAnyRole()` / `hasRole()`
- an `isRole($role)` method
- a `role`, `role_name`, `type` or `user_type` attribute
- a `roles` attribute or relation containing role names

Permissions use `hasPermissionTo()` (Spatie), `hasPermission()` or `can()`.

## Examples

### Only admins and editors

```dotenv
MEDIAFLOW_ROLES=admin,editor
```

### A separate admin guard

```dotenv
MEDIAFLOW_GUARD=admin
```

### A gate

```dotenv
MEDIAFLOW_GATE=manage-media
```

```php
// AppServiceProvider::boot()
Gate::define('manage-media', fn ($user) => $user->is_admin);
```

### Rate limiting and verified email

```dotenv
MEDIAFLOW_CUSTOM_MIDDLEWARE=verified,throttle:60,1
```

### A different URL

```dotenv
MEDIAFLOW_ROUTE_PREFIX=admin/media
```

The components build their URLs from route names such as `route('mediaflow.upload')`, so changing the prefix needs no Blade changes.

## Custom authorization callback

Register a closure for rules that do not fit roles or gates. It receives the resolved user (or `null`) and the request, and must return `true` to allow access:

```php
use MediaFlow\Laravel\Facades\MediaFlow;

public function boot(): void
{
    MediaFlow::authorize(function ($user, $request) {
        return $user?->is_admin || $user?->can('access-media');
    });
}
```

## Using the middleware on your own routes

The middleware is registered as `mediaflow.auth`. Parameters override the config for that route group:

```php
Route::middleware('mediaflow.auth:guard=admin,roles=admin|editor')->group(function () {
    // your media-related routes
});
```

Supported parameters: `guard=`, `roles=` and `permissions=` (separate several values with `|`). A bare value such as `mediaflow.auth:admin` is treated as the guard. Passing any parameter turns authentication on for that group.

## Settings page vs config

Access rules can also be saved on the [Settings page](/config/settings-page). By default they can only **add** restrictions:

- a saved "auth disabled" never overrides `MEDIAFLOW_AUTH_ENABLED=true`, and
- roles, permissions, guard and gate from config win over saved ones.

Set `MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true` if you want the page to be the single source of truth.

## Disabling auth for a local demo

```dotenv
MEDIAFLOW_AUTH_ENABLED=false
```

Only do this on your own machine. With auth off, anyone who can reach the app can upload and delete files and read or change your storage settings.
