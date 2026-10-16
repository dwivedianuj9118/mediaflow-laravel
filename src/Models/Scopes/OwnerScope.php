<?php

namespace MediaFlow\Laravel\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits media and folder queries to the authenticated user's own records when
 * config('mediaflow.scope.user_scoped') is enabled. Queries made without an
 * authenticated user (console commands, queued jobs, signed delivery) are not
 * filtered. Use Model::withoutGlobalScope(OwnerScope::class) to bypass it.
 */
class OwnerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!config('mediaflow.scope.user_scoped', false)) {
            return;
        }

        try {
            if (!function_exists('auth') || !auth()->check()) {
                return;
            }
            $userId = (string) auth()->id();
        } catch (\Throwable $e) {
            return;
        }

        $builder->where($model->qualifyColumn('owner_id'), $userId);
    }
}
