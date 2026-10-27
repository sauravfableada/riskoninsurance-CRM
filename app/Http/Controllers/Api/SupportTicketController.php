<?php

namespace App\Http\Controllers\Api;

use App\Models\ModuleStatusHistory;
use App\Models\Customer;
use App\Models\SupportReply;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware('can:viewAny,' . SupportTicket::class)->only('index');
        $this->middleware('can:create,' . SupportTicket::class)->only('store');
        $this->middleware('can:view,ticket')->only('show');
        $this->middleware('can:update,ticket')->only(['update', 'reply', 'updateStatus']);
        $this->middleware('can:delete,ticket')->only('destroy');

        if (!function_exists('send_ticket_created_notification')) {
            require_once app_path('Helpers/emailSendHelper.php');
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $search = $request->get('search');

        // Apply role-based filtering
        $tickets = $this->scopeOwnedRecords(SupportTicket::with(['customer', 'creator']))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_name', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $tickets->getCollection()->transform(function (SupportTicket $ticket, int $index) use ($tickets) {
            $ticket->row_number = (($tickets->currentPage() - 1) * 10) + $index + 1;

            return $ticket;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tickets retrieved successfully.',
            'data' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $ticket = SupportTicket::create($data);
        $historyEntry = $this->recordStatusHistory($ticket, $data['status'] ?? null, $data['status_comment'] ?? null);
        $ticket->load(['customer', 'creator']);
        app(\App\Services\UserLogService::class)->created($ticket, 'Created a Ticket ' . ($ticket->ticket_name ?: ('ID ' . $ticket->id)));

        \send_ticket_created_notification($ticket);

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully.',
            'data' => $ticket,
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('tickets.index'),
        ], 201);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer', 'creator', 'replies.user']);

        return response()->json([
            'success' => true,
            'message' => 'Ticket retrieved successfully.',
            'data' => $ticket,
        ]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $originalStatus = $ticket->status;
        $validator = Validator::make($request->all(), $this->rules(true), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $data['updated_by'] = auth()->id();

        $ticket->update($data);
        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($ticket, $data['status'] ?? $ticket->status, $data['status_comment'] ?? null);
        }
        $ticket->load(['customer', 'creator']);
        app(\App\Services\UserLogService::class)->updated($ticket, 'Updated a Ticket ' . ($ticket->ticket_name ?: ('ID ' . $ticket->id)));

        return response()->json([
            'success' => true,
            'message' => 'Ticket updated successfully.',
            'data' => $ticket,
            'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
            'redirect' => route('tickets.index', $ticket),
        ]);
    }

    public function destroy(SupportTicket $ticket)
    {
        $ticket->update([
            'deleted_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        app(\App\Services\UserLogService::class)->deleted($ticket, 'Deleted a Ticket ' . ($ticket->ticket_name ?: ('ID ' . $ticket->id)));
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket deleted successfully.',
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'message.required' => 'Reply message is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);

        }

        $reply = SupportReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validator->validated()['message'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $reply->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Reply posted successfully.',
            'reply' => [
                'id' => $reply->id,
                'message' => e($reply->message),
                'user_name' => $reply->user?->name ?? 'User',
                'created_at' => $reply->created_at?->format('d M Y h:i A'),
                'is_current_user' => (int) $reply->user_id === (int) auth()->id(),
            ],
            'ticket_status' => $ticket->status,
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:Open,In Progress,Resolved,Closed'],
        ], [
            'status.required' => 'Status is required.',
            'status.in' => 'Please select a valid status.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket->update([
            'status' => $validator->validated()['status'],
            'updated_by' => auth()->id(),
        ]);
        app(\App\Services\UserLogService::class)->updated($ticket, 'Updated a Ticket ' . ($ticket->ticket_name ?: ('ID ' . $ticket->id)));

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully.',
            'status' => $ticket->status,
        ]);
    }

    private function recordStatusHistory(SupportTicket $ticket, ?string $status, ?string $comment): ?ModuleStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        return ModuleStatusHistory::create([
            'historable_type' => SupportTicket::class,
            'historable_id' => $ticket->id,
            'status' => $status,
            'comment' => filled($comment) ? $comment : null,
            'updated_by' => auth()->id(),
        ]);
    }

    private function serializeHistoryEntry(?ModuleStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        return [
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? auth()->user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }

    private function rules(bool $updating = false): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'ticket_name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:Low,Medium,High,Urgent'],
            'status' => ['required', 'in:Open,In Progress,Resolved,Closed'],
            'description' => ['required', 'string', 'max:2000'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'Please select a valid customer.',
            'ticket_name.required' => 'Ticket name is required.',
            'ticket_name.max' => 'Ticket name must not exceed 255 characters.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Please select a valid priority.',
            'status.required' => 'Status is required.',
            'status.in' => 'Please select a valid status.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description must not exceed 2000 characters.',
        ];
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }
}
