<?php

namespace MediaFlow\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Support\MediaFlowSettingsManager;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeMediaFlowAccess
{
    protected MediaFlowSettingsManager $settings;

    public function __construct(MediaFlowSettingsManager $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  ...$params  Optional parameters passed via route middleware definition
     */
    public function handle(Request $request, Closure $next, ...$params): Response
    {
        // 1. Never block signed delivery streaming, public documentation, or CSRF token retrieval
        if ($request->routeIs('mediaflow.delivery.stream') || $request->routeIs('mediaflow.docs', 'mediaflow.docs.alias') || $request->routeIs('mediaflow.csrf-token')) {
            return $next($request);
        }

        // 2. Parse configuration from middleware arguments, config and database settings.
        //    Config/.env values are a security floor: UI-saved settings may add
        //    restrictions but cannot disable them unless auth.allow_ui_override is true.
        $allowUiOverride = (bool) config('mediaflow.auth.allow_ui_override', false);

        $guard = null;
        $roles = [];
        $permissions = [];

        $configEnabled = $this->toBool(config('mediaflow.auth.enabled', true));
        $dbEnabled = $this->settings->getPersisted('auth.enabled');
        if ($allowUiOverride && $dbEnabled !== null) {
            $authEnabled = $this->toBool($dbEnabled);
        } else {
            $authEnabled = $configEnabled || ($dbEnabled !== null && $this->toBool($dbEnabled));
        }

        // Check if params passed to middleware (e.g. mediaflow.auth:admin or mediaflow.auth:guard=admin,roles=admin|editor)
        if (!empty($params)) {
            $authEnabled = true;
            foreach ($params as $param) {
                if (str_starts_with($param, 'guard=')) {
                    $guard = substr($param, 6);
                } elseif (str_starts_with($param, 'roles=')) {
                    $roles = array_values(array_filter(array_map('trim', explode('|', substr($param, 6)))));
                } elseif (str_starts_with($param, 'permissions=')) {
                    $permissions = array_values(array_filter(array_map('trim', explode('|', substr($param, 12)))));
                } elseif (!$guard) {
                    $guard = $param;
                }
            }
        }

        if (!$guard) {
            $guard = $this->resolveScalar('guard', $allowUiOverride);
        }

        if (empty($roles)) {
            $roles = $this->resolveList('roles', $allowUiOverride);
        }

        if (empty($permissions)) {
            $permissions = $this->resolveList('permissions', $allowUiOverride);
        }

        $gate = $this->resolveScalar('gate', $allowUiOverride);

        // If auth is not enabled, and no roles or permissions or custom callback or gate are required:
        $hasCustomCallback = MediaFlow::hasAuthorizationCallback();

        if (!$authEnabled && empty($roles) && empty($permissions) && empty($gate) && !$hasCustomCallback) {
            return $next($request);
        }

        // 3. Resolve user through specified guard or default guard
        $authGuardInstance = !empty($guard) ? auth()->guard($guard) : auth();
        $user = $authGuardInstance->user();

        // 4. Check Authentication requirement
        if ($authEnabled || !empty($roles) || !empty($permissions)) {
            if (!$authGuardInstance->check() || !$user) {
                return $this->unauthenticatedResponse($request);
            }
        }

        // 5. Check Custom Authorization Callback (e.g. MediaFlow::authorize(fn($user, $req) => ...))
        if ($hasCustomCallback) {
            if (!MediaFlow::isAuthorized($request, $user)) {
                return $this->forbiddenResponse($request, 'Custom authorization callback denied access to MediaFlow.');
            }
        }

        // 6. Check Roles (supports Spatie hasRole/hasAnyRole, model attributes, or custom role methods)
        if (!empty($roles) && $user) {
            if (!$this->userHasAnyRole($user, $roles)) {
                return $this->forbiddenResponse(
                    $request,
                    'You do not possess the required role to access MediaFlow. Required: ' . implode(', ', $roles)
                );
            }
        }

        // 7. Check Permissions
        if (!empty($permissions) && $user) {
            if (!$this->userHasAnyPermission($user, $permissions)) {
                return $this->forbiddenResponse(
                    $request,
                    'You do not possess the required permission to access MediaFlow. Required: ' . implode(', ', $permissions)
                );
            }
        }

        // 8. Check Laravel Gate
        if (!empty($gate)) {
            if (!Gate::forUser($user)->allows($gate)) {
                return $this->forbiddenResponse($request, "Gate check '{$gate}' denied access to MediaFlow.");
            }
        }

        return $next($request);
    }

    /**
     * Check if user matches any of the given roles.
     *
     * @param  mixed  $user
     * @param  array<string>  $roles
     */
    protected function userHasAnyRole($user, array $roles): bool
    {
        // Spatie Laravel-Permission hasAnyRole
        if (method_exists($user, 'hasAnyRole')) {
            try {
                if ($user->hasAnyRole($roles)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // fallback
            }
        }

        // hasRole check
        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                try {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    // continue
                }
            }
        }

        // isRole check
        if (method_exists($user, 'isRole')) {
            foreach ($roles as $role) {
                if ($user->isRole($role)) {
                    return true;
                }
            }
        }

        // Attribute checks: $user->role, $user->role_name, $user->type
        foreach (['role', 'role_name', 'type', 'user_type'] as $attr) {
            if (isset($user->{$attr}) && in_array((string) $user->{$attr}, $roles, true)) {
                return true;
            }
        }

        // Check array roles attribute (e.g. $user->roles array or collection of strings)
        if (isset($user->roles)) {
            $userRoles = is_array($user->roles) ? $user->roles : (method_exists($user->roles, 'pluck') ? $user->roles->pluck('name')->toArray() : []);
            if (!empty(array_intersect($roles, $userRoles))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user matches any of the given permissions.
     */
    protected function userHasAnyPermission($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
                return true;
            }
            if (method_exists($user, 'hasPermission') && $user->hasPermission($permission)) {
                return true;
            }
            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Unauthenticated response (401 or redirect to login).
     */
    protected function unauthenticatedResponse(Request $request): Response
    {
        if ($request->expectsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated. MediaFlow requires authentication.',
                'message' => 'Unauthenticated. MediaFlow requires authentication.',
            ], 401);
        }

        if (Route::has('login')) {
            return redirect()->guest(route('login'));
        }

        return response('Unauthenticated. MediaFlow requires authentication.', 401);
    }

    /**
     * Forbidden response (403).
     */
    protected function forbiddenResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => $message,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }

    /**
     * Resolve roles/permissions: config values always apply; UI values are used when
     * config is empty (or replace config when UI override is explicitly allowed).
     */
    protected function resolveList(string $key, bool $allowUiOverride): array
    {
        $fromConfig = $this->normalizeArray(config("mediaflow.auth.{$key}", []));
        $fromDb = $this->normalizeArray($this->settings->getPersisted("auth.{$key}"));

        if ($allowUiOverride && !empty($fromDb)) {
            return $fromDb;
        }

        return !empty($fromConfig) ? $fromConfig : $fromDb;
    }

    protected function resolveScalar(string $key, bool $allowUiOverride): ?string
    {
        $fromConfig = config("mediaflow.auth.{$key}");
        $fromDb = $this->settings->getPersisted("auth.{$key}");
        $fromConfig = is_string($fromConfig) && trim($fromConfig) !== '' ? trim($fromConfig) : null;
        $fromDb = is_string($fromDb) && trim($fromDb) !== '' ? trim($fromDb) : null;

        return $allowUiOverride ? ($fromDb ?? $fromConfig) : ($fromConfig ?? $fromDb);
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Normalize comma-separated strings or arrays into a clean array of strings.
     */
    protected function normalizeArray($val): array
    {
        if (is_array($val)) {
            return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $val)));
        }

        if (is_string($val) && strlen(trim($val)) > 0) {
            return array_values(array_filter(array_map('trim', explode(',', $val))));
        }

        return [];
    }
}
