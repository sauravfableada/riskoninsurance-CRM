<?php

namespace App\Http\Controllers\Api;

use App\Models\Deal;
use App\Models\FollowUp;
use App\Models\FollowUpStatusHistory;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Meeting;
use App\Models\MeetingStatusHistory;
use App\Models\ModuleStatusHistory;
use App\Models\Pipeline;
use App\Models\Project;
use App\Models\Service;
use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StatusHistoryController extends ApiBaseController
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module' => ['required', 'in:task,followup,lead,project,service,pipeline,meeting,ticket,deal'],
            'record_id' => ['required', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Please fix the highlighted fields.');
        }

        $data = $validator->validated();
        $handler = $this->resolveHandler($data['module']);

        if (!$handler) {
            abort(404);
        }

        $record = $handler['model']::findOrFail($data['record_id']);
        $history = $handler['save']($record, $data['status'] ?? null, $data['comment']);

        $serialized = $handler['serialize']($history);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'history_entry' => $serialized,
            'data' => $serialized,
        ]);
    }

    private function resolveHandler(string $module): ?array
    {
        return match ($module) {
            'task' => [
                'model' => Task::class,
                'save' => function (Task $task, ?string $status, string $comment): ModuleStatusHistory {
                    if ($status !== null && $status !== '') {
                        $task->update(['status' => $status]);
                    }

                    return ModuleStatusHistory::create([
                        'historable_type' => Task::class,
                        'historable_id' => $task->id,
                        'status' => $status ?: $task->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (ModuleStatusHistory $history) => $this->serializeModuleHistory($history),
            ],
            'followup' => [
                'model' => FollowUp::class,
                'save' => function (FollowUp $followUp, ?string $status, string $comment): FollowUpStatusHistory {
                    if ($status !== null && $status !== '') {
                        $followUp->update(['status' => $status, 'updated_by' => Auth::id()]);
                    }

                    return FollowUpStatusHistory::create([
                        'follow_up_id' => $followUp->id,
                        'status' => $status ?: $followUp->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (FollowUpStatusHistory $history) => $this->serializeFollowUpHistory($history),
            ],
            'lead' => [
                'model' => Lead::class,
                'save' => function (Lead $lead, ?string $status, string $comment): LeadStatusHistory {
                    if ($status !== null && $status !== '') {
                        $lead->update(['status' => $status]);
                    }

                    return LeadStatusHistory::create([
                        'lead_id' => $lead->id,
                        'status' => $status ?: $lead->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (LeadStatusHistory $history) => $this->serializeLeadHistory($history),
            ],
            'project' => [
                'model' => Project::class,
                'save' => function (Project $project, ?string $status, string $comment): ModuleStatusHistory {
                    if ($status !== null && $status !== '') {
                        $project->update(['status' => $status, 'updated_by' => Auth::id()]);
                    }

                    return ModuleStatusHistory::create([
                        'historable_type' => Project::class,
                        'historable_id' => $project->id,
                        'status' => $status ?: $project->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (ModuleStatusHistory $history) => $this->serializeModuleHistory($history),
            ],
            'service' => [
                'model' => Service::class,
                'save' => function (Service $service, ?string $status, string $comment): ModuleStatusHistory {
                    if ($status !== null && $status !== '') {
                        $service->update(['status' => $status, 'updated_by' => Auth::id()]);
                    }

                    return ModuleStatusHistory::create([
                        'historable_type' => Service::class,
                        'historable_id' => $service->id,
                        'status' => $status ?: $service->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (ModuleStatusHistory $history) => $this->serializeModuleHistory($history),
            ],
            'pipeline' => [
                'model' => Pipeline::class,
                'save' => function (Pipeline $pipeline, ?string $status, string $comment): ModuleStatusHistory {
                    if ($status !== null && $status !== '') {
                        $pipeline->update(['status' => $status]);
                    }

                    return ModuleStatusHistory::create([
                        'historable_type' => Pipeline::class,
                        'historable_id' => $pipeline->id,
                        'status' => $status ?: $pipeline->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (ModuleStatusHistory $history) => $this->serializeModuleHistory($history),
            ],
            'meeting' => [
                'model' => Meeting::class,
                'save' => function (Meeting $meeting, ?string $status, string $comment): MeetingStatusHistory {
                    if ($status !== null && $status !== '') {
                        $meeting->update(['status' => $status, 'updated_by' => Auth::id()]);
                    }

                    return MeetingStatusHistory::create([
                        'meeting_id' => $meeting->id,
                        'status' => $status ?: $meeting->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (MeetingStatusHistory $history) => $this->serializeMeetingHistory($history),
            ],
            'ticket' => [
                'model' => SupportTicket::class,
                'save' => function (SupportTicket $ticket, ?string $status, string $comment): ModuleStatusHistory {
                    if ($status !== null && $status !== '') {
                        $ticket->update(['status' => $status, 'updated_by' => Auth::id()]);
                    }

                    return ModuleStatusHistory::create([
                        'historable_type' => SupportTicket::class,
                        'historable_id' => $ticket->id,
                        'status' => $status ?: $ticket->status,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (ModuleStatusHistory $history) => $this->serializeModuleHistory($history),
            ],
            'deal' => [
                'model' => Deal::class,
                'save' => function (Deal $deal, ?string $statusId, string $comment): ModuleStatusHistory {
                    $resolvedStatusId = $statusId ?: (string) $deal->status_id;
                    if ($resolvedStatusId !== '' && $resolvedStatusId !== null) {
                        $deal->update(['status_id' => $resolvedStatusId]);
                    }

                    $statusName = Status::find($resolvedStatusId)?->name ?? $deal->status?->name;

                    return ModuleStatusHistory::create([
                        'historable_type' => Deal::class,
                        'historable_id' => $deal->id,
                        'status' => $statusName,
                        'comment' => $comment,
                        'updated_by' => Auth::id(),
                    ]);
                },
                'serialize' => fn (ModuleStatusHistory $history) => $this->serializeModuleHistory($history),
            ],
            default => null,
        };
    }

    private function serializeModuleHistory(ModuleStatusHistory $history): array
    {
        $history->loadMissing('updater');

        return [
            'status_label' => match (strtolower((string) $history->status)) {
                'ready_to_close' => 'Ready to Close',
                'won' => 'Closed Won',
                'lost' => 'Closed Lost',
                default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            },
            'status' => $history->status,
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
            'date' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
        ];
    }

    private function serializeLeadHistory(LeadStatusHistory $history): array
    {
        $history->loadMissing('updater');

        return [
            'status_label' => match (strtolower((string) $history->status)) {
                'ready_to_close' => 'Ready to Close',
                'won' => 'Closed Won',
                'lost' => 'Closed Lost',
                default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            },
            'status' => $history->status,
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
            'date' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
        ];
    }

    private function serializeFollowUpHistory(FollowUpStatusHistory $history): array
    {
        $history->loadMissing('updater');

        return [
            'status_label' => str($history->status)->replace('_', ' ')->title()->toString(),
            'status' => $history->status,
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
            'date' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
        ];
    }

    private function serializeMeetingHistory(MeetingStatusHistory $history): array
    {
        $history->loadMissing('updater');

        return [
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            'status' => $history->status,
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
            'date' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--',
        ];
    }
}
