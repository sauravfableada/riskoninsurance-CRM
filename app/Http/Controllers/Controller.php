<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Scope records to only those owned by the current user.
     * 
     * Admin sees all data.
     * Staff sees only:
     *   - Records they own (user_id = their ID)
     *   - Records assigned to them (assigned_user_id = their ID)
     * 
     * NOTE: created_by is NOT included because if admin reassigns a record,
     * the original creator should no longer see it.
     * 
     * @param Builder $query The query builder
     * @param string $column The column to filter by (default: 'user_id')
     * @return Builder
     */
    protected function scopeOwnedRecords(Builder $query, string $column = 'user_id'): Builder
    {
        $user = auth()->user();

        // No authenticated user = no data
        if (!$user) {
            Log::warning('scopeOwnedRecords: No authenticated user', [
                'path' => request()->path(),
                'method' => request()->method(),
            ]);
            return $query->whereRaw('1 = 0');
        }

        $isAdmin = $user->isAdmin();
        $table = $query->getModel()->getTable();
        $model = $query->getModel();

        Log::debug('scopeOwnedRecords applied', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'is_admin' => $isAdmin,
            'table' => $table,
            'column' => $column,
            'path' => request()->path(),
        ]);

        // Admin sees all data
        if ($isAdmin) {
            Log::debug('scopeOwnedRecords: Admin user - showing all data');
            return $query;
        }

        // Special handling for Customer model
        if ($model instanceof Customer) {
            Log::debug('scopeOwnedRecords: Using visibleToUser for Customer');
            return $query->visibleToUser($user);
        }

        // Staff visibility logic:
        // Show only records where user_id = X OR assigned_user_id = X
        // Fallback to created_by if user_id doesn't exist
        $hasColumn = $this->safeCheckColumn($table, $column);
        $hasAssignedColumn = $this->safeCheckColumn($table, 'assigned_user_id');
        $hasCreatedBy = $this->safeCheckColumn($table, 'created_by');
        
        $conditions = [];
        
        // Use user_id if exists, otherwise fallback to created_by
        if ($hasColumn) {
            $conditions[] = [$column, $user->id];
        } elseif ($hasCreatedBy) {
            // Fallback to created_by when user_id doesn't exist
            $conditions[] = ['created_by', $user->id];
        }
        
        if ($hasAssignedColumn) {
            $conditions[] = ['assigned_user_id', $user->id];
        }
        
        if (!empty($conditions)) {
            return $query->where(function (Builder $q) use ($conditions) {
                foreach ($conditions as $index => $condition) {
                    if ($index === 0) {
                        $q->where($condition[0], $condition[1]);
                    } else {
                        $q->orWhere($condition[0], $condition[1]);
                    }
                }
            });
        }

        // No ownership column found - RESTRICT DATA (security first!)
        Log::warning('scopeOwnedRecords: No ownership column found - restricting data', [
            'table' => $table,
            'tried_columns' => [$column, 'assigned_user_id', 'created_by'],
            'user_id' => $user->id,
        ]);
        
        return $query->whereRaw('1 = 0');
    }

    /**
     * Safely check if a column exists in a table.
     * Handles schema cache issues by using fresh database query.
     */
    protected function safeCheckColumn(string $table, string $column): bool
    {
        try {
            // Clear any cached schema for this table
            $cacheKey = 'schema_columns_' . $table;
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            
            return Schema::hasColumn($table, $column);
        } catch (\Exception $e) {
            Log::error('scopeOwnedRecords: Error checking column', [
                'table' => $table,
                'column' => $column,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resolve the user_id to use for ownership.
     */
    protected function resolveOwnedUserId(?int $assignedUserId = null, ?int $fallbackUserId = null): ?int
    {
        if ($assignedUserId) {
            return $assignedUserId;
        }

        if ($fallbackUserId) {
            return $fallbackUserId;
        }

        return auth()->id();
    }
}
