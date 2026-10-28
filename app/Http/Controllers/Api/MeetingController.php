<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingStatusHistory;
use App\Models\Customer;
use App\Models\User;
use App\Models\Setting;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MeetingController extends ApiBaseController
{
    private function googleService(): ?GoogleCalendarService
    {
        try {
            return new GoogleCalendarService();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $query = $this->scopeOwnedRecords(
            Meeting::with(['customer', 'assignedUser', 'createdBy', 'updatedBy'])
        )->latest();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('agenda', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignedUser', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status if needed
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('scheduled_at', '>=', $request->from_date . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $query->where('scheduled_at', '<=', $request->to_date . ' 23:59:59');
        }

        // Filter by meeting type if needed
        if ($request->has('meeting_type') && !empty($request->meeting_type)) {
            $query->where('meeting_type', $request->meeting_type);
        }
        
        // Filter by created_by
        if ($request->has('created_by') && !empty($request->created_by)) {
            $query->where('created_by', $request->created_by);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
        $meetings = $query->paginate($perPage);

        // Check if this is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $meetings,
                'message' => 'Meetings retrieved successfully'
            ]);
        }

        // For non-AJAX requests, return the view
        return view('meetings.index', compact('meetings'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('create', Meeting::class);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'meeting_type' => ['nullable', 'in:online,offline,phone,video'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'address' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ], [
            // Custom error messages
            'title.required' => 'Please enter the meeting title.',
            'title.max' => 'Meeting title cannot exceed 255 characters.',

            'customer_id.required' => 'Please select a customer.',
            'customer_id.integer' => 'Invalid customer selected.',
            'customer_id.exists' => 'The selected customer does not exist in our records.',

            'assigned_user_id.integer' => 'Invalid staff selected.',
            'assigned_user_id.exists' => 'The selected staff does not exist in our records.',

            'scheduled_at.required' => 'Please select the scheduled date and time.',
            'scheduled_at.date' => 'Please enter a valid date and time format.',

            'meeting_type.required' => 'Please select a meeting type.',
            'meeting_type.in' => 'Please select a valid meeting type (Online, Offline, Phone, or Video).',

            'status.required' => 'Please select a status.',
            'status.in' => 'Please select a valid status (Scheduled, Completed, or Cancelled).',

            'address.max' => 'Address cannot exceed 255 characters.',

            'agenda.required' => 'Please enter the meeting agenda.',
        ]);

        $validator->after(function ($validator) use ($request) {
            // Check if scheduled_at is in the past for new meetings
            if ($request->scheduled_at && \Carbon\Carbon::parse($request->scheduled_at)->isPast()) {
                $validator->errors()->add('scheduled_at', 'Scheduled date and time cannot be in the past.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        if (Meeting::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId($data['assigned_user_id'] ?? null, $user->id);
        }
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        $meeting = Meeting::create($data);
        $historyEntry = $this->recordStatusHistory($meeting, $data['status'] ?? null, $data['status_comment'] ?? null);
        app(\App\Services\UserLogService::class)->created($meeting, 'Scheduled a Meeting ' . ($meeting->title ?: ('ID ' . $meeting->id)));

        // Auto-sync to Google Calendar if authenticated and auto-sync is enabled (OPTIONAL - don't fail if not authenticated)
        try {
            $googleService = $this->googleService();
            if ($googleService && $googleService->isConfigured() && $googleService->isAuthenticated() && $googleService->isAutoSyncEnabled()) {
                $eventId = $googleService->createEvent($meeting);
                if ($eventId) {
                    $meeting->update([
                        'google_event_id' => $eventId,
                        'is_synced' => true,
                        'synced_at' => now(),
                    ]);
                    Log::info('Meeting synced to Google Calendar', [
                        'meeting_id' => $meeting->id,
                        'google_event_id' => $eventId,
                    ]);
                } else {
                    Log::warning('Failed to create meeting in Google Calendar', [
                        'meeting_id' => $meeting->id,
                    ]);
                }
            } else {
                Log::debug('Google Calendar sync skipped - not authenticated or not configured', [
                    'meeting_id' => $meeting->id,
                    'is_configured' => $googleService ? $googleService->isConfigured() : false,
                    'is_authenticated' => $googleService ? $googleService->isAuthenticated() : false,
                    'is_auto_sync_enabled' => $googleService ? $googleService->isAutoSyncEnabled() : false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error syncing meeting to Google Calendar', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the meeting creation if Google Calendar sync fails
        }

        // WhatsApp notification to customer
        try {
            $meeting->load(['customer', 'assignedUser']);

            $customerPhone = $meeting->customer?->whatsapp ?: $meeting->customer?->phone;
            $scheduledText = $meeting->scheduled_at ? \Illuminate\Support\Carbon::parse($meeting->scheduled_at)->format('d M Y h:i A') : '';
            $meetingType = $meeting->meeting_type ? ucfirst($meeting->meeting_type) : 'Meeting';
            $addressText = $meeting->address ?: '--';
            $agendaText = $meeting->agenda ?: '';
            $staffName = $meeting->assignedUser?->name ?? 'Staff';

            if ($customerPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'meeting_scheduled_customer',
                    $customerPhone,
                    [
                        $meeting->customer?->name ?? 'Customer',
                        $staffName,
                        $meeting->title ?? 'Meeting',
                        $scheduledText,
                        $meetingType,
                        $addressText,
                        $agendaText,
                    ],
                    $meeting->id
                );
            }

            $staffPhone = $meeting->assignedUser?->whatsapp ?: $meeting->assignedUser?->phone;
            if ($staffPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'meeting_scheduled_staff',
                    $staffPhone,
                    [
                        $staffName,
                        $meeting->customer?->name ?? 'Customer',
                        $meeting->title ?? 'Meeting',
                        $scheduledText,
                        $meetingType,
                        $addressText,
                        $agendaText,
                    ],
                    $meeting->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Meeting create WhatsApp block failed', [
                'meeting_id' => $meeting->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meeting scheduled successfully.',
            'data' => $meeting->fresh(['customer', 'assignedUser']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('meetings.index'),
        ], 201);
    }

    public function show(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $meeting = Meeting::with(['customer', 'assignedUser', 'createdBy', 'updatedBy'])
            ->findOrFail($id);
        $this->authorize('view', $meeting);

        return response()->json([
            'success' => true,
            'message' => 'Meeting retrieved successfully.',
            'data' => $meeting,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $meeting = Meeting::findOrFail($id);
        $this->authorize('update', $meeting);
        $originalStatus = $meeting->status;
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'meeting_type' => ['nullable', 'in:online,offline,phone,video'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'address' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ], [
            // Custom error messages
            'title.required' => 'Please enter the meeting title.',
            'title.max' => 'Meeting title cannot exceed 255 characters.',

            'customer_id.required' => 'Please select a customer.',
            'customer_id.integer' => 'Invalid customer selected.',
            'customer_id.exists' => 'The selected customer does not exist in our records.',

            'assigned_user_id.integer' => 'Invalid staff selected.',
            'assigned_user_id.exists' => 'The selected staff does not exist in our records.',

            'scheduled_at.required' => 'Please select the scheduled date and time.',
            'scheduled_at.date' => 'Please enter a valid date and time format.',

            'meeting_type.required' => 'Please select a meeting type.',
            'meeting_type.in' => 'Please select a valid meeting type (Online, Offline, Phone, or Video).',

            'status.required' => 'Please select a status.',
            'status.in' => 'Please select a valid status (Scheduled, Completed, or Cancelled).',

            'address.max' => 'Address cannot exceed 255 characters.',

            'agenda.required' => 'Please enter the meeting agenda.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $this->ensureVisibleCustomer((int) $data['customer_id']);
        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        if (Meeting::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId(
                $data['assigned_user_id'] ?? $meeting->assigned_user_id,
                $meeting->created_by ?? $meeting->user_id ?? $user->id
            );
        }
        $data['updated_by'] = $user->id;

        $meeting->update($data);

        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($meeting, $data['status'] ?? $meeting->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($meeting, 'Updated a Meeting ' . ($meeting->title ?: ('ID ' . $meeting->id)));

        try {
            $meeting->load(['customer', 'assignedUser']);

            $customerPhone = $meeting->customer?->whatsapp ?: $meeting->customer?->phone;
            $staffPhone = $meeting->assignedUser?->whatsapp ?: $meeting->assignedUser?->phone;
            $staffName = $meeting->assignedUser?->name ?? (auth()->user()?->name ?? 'Staff');
            $customerName = $meeting->customer?->name ?? 'Customer';
            $scheduledText = $meeting->scheduled_at ? \Illuminate\Support\Carbon::parse($meeting->scheduled_at)->format('d M Y h:i A') : '';
            $meetingType = $meeting->meeting_type ? ucfirst($meeting->meeting_type) : 'Meeting';
            $addressText = $meeting->address ?: '--';
            $agendaText = $meeting->agenda ?: '';
            $customerPayload = [
                $customerName,
                $meeting->title ?? 'Meeting',
                $customerName,
                $staffName,
                $scheduledText,
                $meetingType,
                $addressText,
                $agendaText,
            ];

            $staffPayload = [
                $staffName,
                $meeting->title ?? 'Meeting',
                $customerName,
                $staffName,
                $scheduledText,
                $meetingType,
                $addressText,
                $agendaText,
            ];

            if ($customerPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'meeting_updated',
                    $customerPhone,
                    $customerPayload,
                    $meeting->id
                );
            }

            if ($staffPhone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'meeting_updated',
                    $staffPhone,
                    $staffPayload,
                    $meeting->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Meeting update WhatsApp block failed', [
                'meeting_id' => $meeting->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // Auto-update in Google Calendar if synced and auto-sync is enabled
        $googleService = $this->googleService();
        if ($googleService && $googleService->isAuthenticated() && $googleService->isAutoSyncEnabled()) {
            if ($meeting->google_event_id) {
                // Update existing event
                $updateSuccess = $googleService->updateEvent($meeting);
                if ($updateSuccess) {
                    $meeting->update(['synced_at' => now()]);
                } else {
                    Log::warning('Failed to update meeting in Google Calendar', [
                        'meeting_id' => $meeting->id,
                        'google_event_id' => $meeting->google_event_id,
                    ]);
                }
            } else {
                // Create new event if not synced yet
                $eventId = $googleService->createEvent($meeting);
                if ($eventId) {
                    $meeting->update([
                        'google_event_id' => $eventId,
                        'is_synced' => true,
                        'synced_at' => now(),
                    ]);
                } else {
                    Log::warning('Failed to create meeting in Google Calendar', [
                        'meeting_id' => $meeting->id,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Meeting updated successfully.',
            'data' => $meeting->fresh(['customer', 'assignedUser']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
            'redirect' => route('meetings.index', $meeting),
        ]);

    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $meeting = Meeting::findOrFail($id);
        $this->authorize('delete', $meeting);

        // Store google_event_id before deleting
        $googleEventId = $meeting->google_event_id;

        $meeting->deleted_by = $user->id;
        $meeting->save();
        app(\App\Services\UserLogService::class)->deleted($meeting, 'Deleted a Meeting ' . ($meeting->title ?: ('ID ' . $meeting->id)));
        $meeting->delete();

        // Delete from Google Calendar if synced and auto-sync is enabled
        if ($googleEventId) {
            try {
                $googleService = $this->googleService();
                if ($googleService && $googleService->isAuthenticated() && $googleService->isAutoSyncEnabled()) {
                    $deleteSuccess = $googleService->deleteEvent($meeting);
                    if (!$deleteSuccess) {
                        Log::warning('Failed to delete meeting from Google Calendar', [
                            'meeting_id' => $id,
                            'google_event_id' => $googleEventId,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Error deleting meeting from Google Calendar', [
                    'meeting_id' => $id,
                    'google_event_id' => $googleEventId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Meeting deleted successfully.'
        ], 200);
    }

    public function getCustomers()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $customers = $this->scopeOwnedRecords(Customer::select('id', 'name'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function getUsers()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $users = $user->isAdmin()
            ? User::select('id', 'name')->orderBy('name')->get()
            : User::select('id', 'name')->whereKey($user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get Google Calendar authentication status
     */
    public function googleAuthStatus()
    {
        if (Setting::query()->where('key', 'google_connection_enabled')->value('value') !== '1') {
            return response()->json(['success' => false, 'message' => 'Google connection is disabled.'], 404);
        }
        $googleService = $this->googleService();
        $isConfigured = $googleService ? $googleService->isConfigured() : false;
        $isAuthenticated = $googleService ? $googleService->isAuthenticated() : false;

        return response()->json([
            'success' => true,
            'data' => [
                'available' => (bool) $googleService,
                'is_configured' => $isConfigured,
                'is_authenticated' => $isAuthenticated,
            ]
        ]);
    }

    /**
     * Get Google OAuth URL
     */
    public function googleAuthUrl()
    {
        if (Setting::query()->where('key', 'google_connection_enabled')->value('value') !== '1') {
            return response()->json(['success' => false, 'message' => 'Google connection is disabled.'], 404);
        }
        $googleService = $this->googleService();
        if (!$googleService || !$googleService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Google Calendar settings are incomplete. Please save Google client ID and secret first.',
            ], 422);
        }

        $authUrl = $googleService->getAuthUrl();

        return response()->json([
            'success' => true,
            'data' => [
                'auth_url' => $authUrl,
            ]
        ]);
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $googleService = $this->googleService();
        if (!$googleService || !$googleService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Google Calendar settings are incomplete. Please save Google client ID and secret first.',
            ], 422);
        }

        $success = $googleService->handleCallback($request->code);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Google Calendar connected successfully!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to connect Google Calendar. Please try again.',
        ], 400);
    }

    /**
     * Disconnect Google Calendar
     */
    public function googleDisconnect()
    {
        $googleService = $this->googleService();
        if (!$googleService) {
            return response()->json([
                'success' => false,
                'message' => 'Google Calendar integration is not available in this environment.',
            ], 503);
        }

        $success = $googleService->disconnect();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Google Calendar disconnected.' : 'Failed to disconnect.',
        ]);
    }

    /**
     * Sync a meeting to Google Calendar
     */
    public function syncToGoogle(Request $request, string $id)
    {
        try {
            $meeting = Meeting::findOrFail($id);
            $this->authorize('update', $meeting);

            // Validation of meeting fields required for Google Calendar
            if (empty($meeting->title)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting title is required for Google Calendar sync.',
                ], 400);
            }

            if (!$meeting->scheduled_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting schedule date and time are required for Google Calendar sync.',
                ], 400);
            }

            $googleService = $this->googleService();
            if (!$googleService || !$googleService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar integration is not configured. Please contact administrator.',
                ], 503);
            }

            if (!$googleService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your Google account is not connected or the session has expired. Please connect your account in settings.',
                ], 401); // 401 is more appropriate for authentication issues
            }

            // The service already handles token refresh internally via authorizedRequest
            $success = $googleService->syncMeeting($meeting);

            if ($success) {
                $meeting->update([
                    'is_synced' => true,
                    'synced_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Meeting synced to Google Calendar successfully!',
                    'data' => [
                        'google_event_id' => $meeting->google_event_id,
                        'is_synced' => $meeting->is_synced,
                        'synced_at' => $meeting->synced_at->format('Y-m-d H:i:s'),
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync meeting to Google Calendar. Please check your connection and try again.',
            ], 400);

        } catch (\Throwable $e) {
            Log::error('Meeting Sync Error:', [
                'meeting_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove meeting from Google Calendar
     */
    public function removeFromGoogle(Request $request, string $id)
    {
        $meeting = Meeting::findOrFail($id);
        $this->authorize('update', $meeting);

        if (!$meeting->google_event_id) {
            return response()->json([
                'success' => false,
                'message' => 'Meeting is not synced with Google Calendar.',
            ], 400);
        }

        $googleService = $this->googleService();
        if (!$googleService) {
            return response()->json([
                'success' => false,
                'message' => 'Google Calendar integration is not available in this environment.',
            ], 503);
        }

        $success = $googleService->deleteEvent($meeting);

        if ($success) {
            $meeting->update([
                'google_event_id' => null,
                'is_synced' => false,
                'synced_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meeting removed from Google Calendar successfully!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove meeting from Google Calendar.',
        ], 400);
    }

    /**
     * Get Google Calendar events
     */
    public function googleEvents(Request $request)
    {
        $googleService = $this->googleService();
        if (!$googleService) {
            return response()->json([
                'success' => false,
                'message' => 'Google Calendar integration is not available in this environment.',
            ], 503);
        }

        if (!$googleService->isAuthenticated()) {
            return response()->json([
                'success' => false,
                'message' => 'Google Calendar is not connected.',
            ], 400);
        }

        $maxResults = $request->input('max_results', 10);
        $events = $googleService->getEvents($maxResults);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    private function recordStatusHistory(Meeting $meeting, ?string $status, ?string $comment): ?MeetingStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return MeetingStatusHistory::create([
                'meeting_id' => $meeting->id,
                'status' => $status ?? $meeting->status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Meeting status history save skipped.', [
                'meeting_id' => $meeting->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?MeetingStatusHistory $history): ?array
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

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function ensureAssignableUser(?int $assignedUserId, $user): void
    {
        if (!$assignedUserId || $user->isAdmin()) {
            return;
        }

        abort_unless((int) $assignedUserId === (int) $user->id, 403, 'You can only assign records to yourself.');
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $meeting = Meeting::find($id);

        if (!$meeting) {
            return $this->error('Meeting not found', 404);
        }

        $this->authorize('update', $meeting);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,completed,cancelled',
            'status_comment' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $meeting->status;
        $newStatus = $request->status;

        $meeting->update([
            'status' => $newStatus,
            'updated_by' => $user->id
        ]);

        $this->recordStatusHistory($meeting, $newStatus, $request->status_comment);

        app(\App\Services\UserLogService::class)->updated(
            $meeting,
            'Updated status from ' . ucfirst($oldStatus) . ' to ' . ucfirst($newStatus)
        );

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $meeting
        ]);
    }
}
