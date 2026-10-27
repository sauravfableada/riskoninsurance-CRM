<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Pipeline;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\UserLog;

class UserLogService
{
    private ?bool $supportsDetailsColumn = null;

    /**
     * @var array<int, class-string<Model>>
     */
    private array $trackedModels = [
        Customer::class,
        Deal::class,
        FollowUp::class,
        Invoice::class,
        Lead::class,
        Meeting::class,
        Pipeline::class,
        Product::class,
        Project::class,
        Quotation::class,
        Service::class,
        SupportTicket::class,
        Task::class,
        User::class,
    ];

    public function created(Model $model, ?string $message = null): void
    {
        if (!$this->shouldLogModel($model)) {
            return;
        }

        $snapshot = $this->snapshot($model);

        $this->log($model, 'ADD', $message, [
            'record_name' => $this->resolveRecordName($model),
            'changes' => $this->formatAddedChanges($snapshot),
        ]);
    }

    public function updated(Model $model, ?string $message = null): void
    {
        if (!$this->shouldLogModel($model)) {
            return;
        }

        $changes = $this->formatUpdatedChanges($model);

        if ($changes === []) {
            return;
        }

        $this->log($model, 'UPDATE', $message, [
            'record_name' => $this->resolveRecordName($model),
            'changes' => $changes,
        ]);
    }

    public function deleted(Model $model, ?string $message = null): void
    {
        if (!$this->shouldLogModel($model)) {
            return;
        }

        $snapshot = $this->snapshot($model);

        $this->log($model, 'DELETE', $message, [
            'record_name' => $this->resolveRecordName($model),
            'changes' => $this->formatDeletedChanges($snapshot),
        ]);
    }

    public function log(Model $model, string $action, ?string $message = null, ?array $details = null): void
    {
        $action = strtoupper($action);
        $userId = $this->resolveActionUserId();

        $payload = [
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'action' => $action,
            'message' => $message ?: $this->buildMessage($model, $action),
            'actioned_by' => $userId,
            'created_by' => $userId,
            'updated_by' => $action === 'UPDATE' ? $userId : null,
            'deleted_by' => $action === 'DELETE' ? $userId : null,
        ];

        if ($this->userLogsSupportsDetailsColumn()) {
            $payload['details'] = $details;
        }

        UserLog::create($payload);
    }

    public function shouldLogModel(Model $model): bool
    {
        if ($model instanceof UserLog) {
            return false;
        }

        return in_array($model::class, $this->trackedModels, true);
    }

    public function buildMessage(Model $model, string $action): string
    {
        $module = Str::headline(class_basename($model));
        $recordName = $this->resolveRecordName($model);

        return match ($action) {
            'ADD' => "Created a {$module} {$recordName}",
            'DELETE' => "Deleted a {$module} {$recordName}",
            default => "Updated a {$module} {$recordName}",
        };
    }

    private function resolveRecordName(Model $model): string
    {
        $value = collect([
            data_get($model, 'name'),
            data_get($model, 'title'),
            data_get($model, 'ticket_name'),
            data_get($model, 'pipeline_name'),
            data_get($model, 'service_name'),
            data_get($model, 'project_code'),
            data_get($model, 'number'),
            data_get($model, 'purpose'),
        ])->first(fn($item) => filled($item));

        return filled($value) ? (string) $value : ('ID ' . $model->getKey());
    }

    private function snapshot(Model $model): array
    {
        return collect($model->attributesToArray())
            ->except([
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->map(fn($value) => $this->normalizeValue($value))
            ->all();
    }

    private function formatAddedChanges(array $snapshot): array
    {
        return collect($snapshot)
            ->filter(fn($value, $field) => !$this->shouldIgnoreField($field, $value))
            ->map(fn($value, $field) => [
                'field' => $field,
                'label' => Str::headline($field),
                'type' => 'added',
                'new' => $value,
            ])
            ->values()
            ->all();
    }

    private function formatDeletedChanges(array $snapshot): array
    {
        return collect($snapshot)
            ->filter(fn($value, $field) => !$this->shouldIgnoreField($field, $value))
            ->map(fn($value, $field) => [
                'field' => $field,
                'label' => Str::headline($field),
                'type' => 'deleted',
                'old' => $value,
            ])
            ->values()
            ->all();
    }

    private function formatUpdatedChanges(Model $model): array
    {
        return collect($model->getChanges())
            ->except([
                'updated_at',
                'created_at',
                'deleted_at',
            ])
            ->map(function ($value, $field) use ($model) {
                if ($this->shouldIgnoreField($field, $value)) {
                    return null;
                }

                return [
                    'field' => $field,
                    'label' => Str::headline($field),
                    'type' => 'updated',
                    'old' => null,
                    'new' => $this->normalizeValue(data_get($model, $field, $value)),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            return $value->all();
        }

        if ($value instanceof Model) {
            return method_exists($value, 'getKey') ? $value->getKey() : (string) $value;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return collect($value)->map(fn($item) => $this->normalizeValue($item))->all();
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof User) {
            return $value->name;
        }

        return $value;
    }

    private function shouldIgnoreField(string $field, mixed $value): bool
    {
        if (in_array($field, ['id', 'created_by', 'updated_by', 'deleted_by'], true)) {
            return true;
        }

        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }

    private function resolveActionUserId(): ?int
    {
        try {
            return request()->user()?->id ?? auth()->id();
        } catch (\Throwable) {
            return null;
        }
    }

    private function userLogsSupportsDetailsColumn(): bool
    {
        if ($this->supportsDetailsColumn !== null) {
            return $this->supportsDetailsColumn;
        }

        try {
            return $this->supportsDetailsColumn = Schema::hasColumn('user_logs', 'details');
        } catch (\Throwable) {
            return $this->supportsDetailsColumn = false;
        }
    }
}
