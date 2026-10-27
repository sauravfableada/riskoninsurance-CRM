<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait OwnedByUser
{
    protected static array $ownedByUserColumnCache = [];

    public static function bootOwnedByUser(): void
    {
        static::creating(function ($model) {
            $userId = request()->user()?->id;

            if (
                $userId
                && static::hasOwnedByUserColumn($model)
                && empty($model->user_id)
            ) {
                $model->user_id = $userId;
            }
        });

    }

    public static function supportsOwnedByUserColumn(): bool
    {
        return static::hasOwnedByUserColumn(new static());
    }

    public static function syncOwnedUserFromAssignee($model): void
    {
        $userId = request()->user()?->id;

        if (!static::hasOwnedByUserColumn($model)) {
            return;
        }

        if (isset($model->assigned_user_id) && !empty($model->assigned_user_id)) {
            $model->user_id = (int) $model->assigned_user_id;
            return;
        }

        if (empty($model->user_id) && $userId) {
            $model->user_id = $userId;
        }
    }

    protected static function hasOwnedByUserColumn($model): bool
    {
        $table = $model->getTable();

        if (!array_key_exists($table, static::$ownedByUserColumnCache)) {
            static::$ownedByUserColumnCache[$table] = Schema::hasColumn($table, 'user_id');
        }

        return static::$ownedByUserColumnCache[$table];
    }
}
