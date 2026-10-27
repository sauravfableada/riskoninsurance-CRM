<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCustomFields;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Schema;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, HasCustomFields, SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country_code',
        'whatsapp',
        'whatsapp_country_code',
        'address',
        'dob',
        'anniversary_date',
        'company_name',
        'website',
        'tax_number',
        'image',
        'type',
        'country_id',
        'city_id',
        'is_active',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Customer is not an admin user
     */
    public function isAdmin(): bool
    {
        return false;
    }

    /**
     * Customer is not a super admin
     */
    public function isSuperAdmin(): bool
    {
        return false;
    }

    /**
     * Customer has no internal CRM staff matrix permissions
     */
    public function hasMatrixPermission($permission): bool
    {
        return false;
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function policies()
    {
        return $this->hasMany(Policy::class, 'holder_id');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class)->orderByDesc('scheduled_at');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class)->latest();
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'customer_id')->latest('follow_up_at');
    }

    public function scopeVisibleToUser(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        $table = $this->getTable();
        $userId = (int) $user->id;

        return $query->where(function (Builder $sub) use ($table, $userId) {
            // Direct customer ownership - staff/sub_agent created or assigned this customer
            if (Schema::hasColumn($table, 'user_id')) {
                $sub->where($table . '.user_id', $userId);
            }

            if (Schema::hasColumn($table, 'created_by')) {
                $sub->orWhere($table . '.created_by', $userId);
            }

            // Customer has policies owned by or created by this user
            $sub->orWhereExists(function ($policySub) use ($table, $userId) {
                $policySub->selectRaw('1')
                    ->from('policies')
                    ->whereColumn('policies.holder_id', $table . '.id')
                    ->where(function ($pq) use ($userId) {
                        $pq->where('policies.user_id', $userId)
                           ->orWhere('policies.created_by', $userId);
                    })
                    ->whereNull('policies.deleted_at');
            });

            // Assigned module work makes the customer visible but not editable
            // (task, follow-up, meeting, deal, project, invoice, ticket assigned to this staff)
            $this->applyAssignedModuleVisibility($sub, $table, $userId);
        });
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        return $query->visibleToUser($user);
    }

    public function isOwnedBy(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // Only direct ownership counts - user_id must match
        $userId = (int) $user->id;
        $ownerId = (int) ($this->user_id ?? 0);

        return $ownerId === $userId;
    }

    private function applyAssignedModuleVisibility(Builder $query, string $customerTable, int $userId): void
    {
        // Tables that have both customer_id and assigned_user_id
        $assignedTables = [
            'deals',
            'follow_ups',
            'meetings',
            'projects',
            'invoices',
        ];

        foreach ($assignedTables as $assignedTable) {
            if (!Schema::hasColumn($assignedTable, 'customer_id') || !Schema::hasColumn($assignedTable, 'assigned_user_id')) {
                continue;
            }

            $query->orWhereExists(function ($subQuery) use ($assignedTable, $customerTable, $userId) {
                $subQuery->selectRaw('1')
                    ->from($assignedTable)
                    ->whereColumn($assignedTable . '.customer_id', $customerTable . '.id')
                    ->where($assignedTable . '.assigned_user_id', $userId)
                    ->whereNull($assignedTable . '.deleted_at');
            });
        }

        // Tasks have a different structure (related_type, related_id or project_id)
        if (
            Schema::hasColumn('tasks', 'assigned_user_id')
            && Schema::hasColumn('tasks', 'related_type')
            && Schema::hasColumn('tasks', 'related_id')
        ) {
            $query->orWhereExists(function ($subQuery) use ($customerTable, $userId) {
                $subQuery->selectRaw('1')
                    ->from('tasks')
                    ->where('tasks.assigned_user_id', $userId)
                    ->whereNull('tasks.deleted_at')
                    ->where(function ($taskQuery) use ($customerTable) {
                        $taskQuery->where(function ($customerTask) use ($customerTable) {
                            $customerTask->where('tasks.related_type', 'customer')
                                ->whereColumn('tasks.related_id', $customerTable . '.id');
                        });

                        if (Schema::hasColumn('tasks', 'project_id')) {
                            $taskQuery->orWhereExists(function ($projectTask) use ($customerTable) {
                                $projectTask->selectRaw('1')
                                    ->from('projects')
                                    ->whereColumn('projects.id', 'tasks.project_id')
                                    ->whereColumn('projects.customer_id', $customerTable . '.id')
                                    ->whereNull('projects.deleted_at');
                            });
                        }
                    });
            });
        }
    }

    /**
     * Check if this customer has any assigned records for the given user.
     * Used to determine if customer is visible due to assigned work.
     */
    public function hasAssignedRecordsForUser(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (!$user || $user->isAdmin()) {
            return false;
        }

        $userId = $user->id;
        $customerId = $this->id;

        // Check deals
        if (Deal::where('customer_id', $customerId)->where('assigned_user_id', $userId)->exists()) {
            return true;
        }

        // Check follow_ups
        if (FollowUp::where('customer_id', $customerId)->where('assigned_user_id', $userId)->exists()) {
            return true;
        }

        // Check meetings
        if (Meeting::where('customer_id', $customerId)->where('assigned_user_id', $userId)->exists()) {
            return true;
        }

        // Check projects
        if (Project::where('customer_id', $customerId)->where('assigned_user_id', $userId)->exists()) {
            return true;
        }

        // Check invoices
        if (Invoice::where('customer_id', $customerId)->where('assigned_user_id', $userId)->exists()) {
            return true;
        }

        // Check tasks (related to customer or customer's project)
        if (Task::where('assigned_user_id', $userId)
            ->where(function ($q) use ($customerId) {
                $q->where('related_type', 'customer')
                    ->where('related_id', $customerId)
                    ->orWhereHas('project', fn($pq) => $pq->where('customer_id', $customerId));
            })->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if customer can be edited/deleted by the user.
     * Only true if user owns the customer (user_id matches), not just visible via assignment.
     */
    public function canUserModify(?User $user = null): bool
    {
        return $this->isOwnedBy($user);
    }
}
