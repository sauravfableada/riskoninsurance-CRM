<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

// Cache buster: 2026-04-07-12:45:00
trait Blameable
{
    protected static array $blameableColumnCache = [];

    public static function bootBlameable(): void
    {
        static::creating(function (Model $model) {
            $userId = static::resolveBlameableUserId();

            if (!$userId || $model->getAttribute('created_by') !== null || !static::hasBlameableColumn($model, 'created_by')) {
                return;
            }

            $model->setAttribute('created_by', $userId);
        });

        static::created(function (Model $model) {
            static::writeUserLog($model, 'created');
        });

        static::updating(function (Model $model) {
            $userId = static::resolveBlameableUserId();

            if (
                !$userId
                || !static::hasBlameableColumn($model, 'updated_by')
                || $model->isDirty('updated_by')
            ) {
                return;
            }

            $model->setAttribute('updated_by', $userId);
        });

        static::updated(function (Model $model) {
            static::writeUserLog($model, 'updated');
        });

        static::deleting(function (Model $model) {
            $userId = static::resolveBlameableUserId();

            if (
                !$userId
                || !method_exists($model, 'isForceDeleting')
                || $model->isForceDeleting()
                || $model->getAttribute('deleted_by') !== null
                || !static::hasBlameableColumn($model, 'deleted_by')
            ) {
                return;
            }

            $model->setAttribute('deleted_by', $userId);

            // Query builder bulk updates bypass model events; use model instance
            // operations when blameable fields must be populated automatically.
            // SoftDeletes does not persist arbitrary dirty attributes during the
            // delete query, so deleted_by is written directly without recursion.
            if ($model->exists) {
                $model->newQueryWithoutScopes()
                    ->whereKey($model->getKey())
                    ->update([
                        'deleted_by' => $userId,
                    ]);
            }
        });

        static::deleted(function (Model $model) {
            static::writeUserLog($model, 'deleted');
        });
    }

    public function creator(): BelongsTo
    {
        return $this->blameableUserRelation('created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->blameableUserRelation('updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->blameableUserRelation('deleted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->updater();
    }

    public function deletedBy(): BelongsTo
    {
        return $this->deleter();
    }

    protected static function hasBlameableColumn(Model $model, string $column): bool
    {
        $table = $model->getTable();

        if (!isset(static::$blameableColumnCache[$table][$column])) {
            static::$blameableColumnCache[$table][$column] = Schema::hasColumn($table, $column);
        }

        return static::$blameableColumnCache[$table][$column];
    }

    protected static function resolveBlameableUserId(): ?int
    {
        try {
            $requestUserId = app()->bound('request') ? request()->user()?->id : null;

            return $requestUserId ?? auth()->id();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function blameableUserRelation(string $column): BelongsTo
    {
        return $this->belongsTo(User::class, $column);
    }

    protected static function writeUserLog(Model $model, string $event): void
    {
        try {
            $service = app(\App\Services\UserLogService::class);

            if (!$service->shouldLogModel($model)) {
                return;
            }

            match ($event) {
                'created' => $service->created($model),
                'updated' => $service->updated($model),
                'deleted' => $service->deleted($model),
                default => null,
            };
        } catch (\Throwable) {
            // Logging must never break the main persistence flow.
        }
    }
}
