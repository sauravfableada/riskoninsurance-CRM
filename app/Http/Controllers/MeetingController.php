<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Meeting;
use App\Models\Customer;
use App\Models\User;
use App\Models\Setting;
use App\Services\GoogleCalendarService;

class MeetingController extends Controller
{
    public function index()
    {
        $users = $this->selectableUsers(['admin', 'staff']);
        return view('crm.meetings.index', compact('users'));
    }

    public function create()
    {
        $customers = $this->visibleCustomers();
        $users = $this->selectableUsers(['admin', 'staff']);

        return view('crm.meetings.create', compact('customers', 'users'));
    }

    public function edit(string $id)
    {
        $meeting = Meeting::with(['customer', 'assignedUser', 'statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $meeting);
        $customers = $this->visibleCustomers();

        if ($meeting->customer && !$customers->contains('id', $meeting->customer_id)) {
            $customers->push($meeting->customer);
        }

        $users = $this->selectableUsers(['admin', 'staff']);

        return view('crm.meetings.edit', compact('meeting', 'users', 'customers'));
    }

    public function show(string $id)
    {
        $meeting = Meeting::with(['customer', 'assignedUser', 'createdBy', 'updatedBy', 'statusHistories.updater'])->findOrFail($id);
        $this->authorize('view', $meeting);
        return view('crm.meetings.show', compact('meeting'));
    }

    public function export(Request $request)
    {
        $fileName = 'meetings_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Meeting::with(['customer', 'assignedUser', 'createdBy'])
        )
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('meeting_type', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('assignedUser', fn($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->assigned_user_id, fn($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $meetings = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Title', 'Customer', 'Assigned Staff', 'Meeting Type', 'Status', 'Location', 'Scheduled At', 'Google Sync', 'Created By', 'Created At'];

        $callback = function () use ($meetings, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($meetings as $meeting) {
                fputcsv($file, [
                    $i++,
                    $meeting->title,
                    $meeting->customer?->name ?? '--',
                    $meeting->assignedUser?->name ?? 'Unassigned',
                    $meeting->meeting_type ? ucfirst($meeting->meeting_type) : '--',
                    $meeting->status ? ucfirst($meeting->status) : '--',
                    $meeting->address ?? '--',
                    $meeting->scheduled_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                    $meeting->is_synced ? 'Synced' : 'Not Synced',
                    $meeting->createdBy?->name ?? '--',
                    $meeting->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function selectableUsers(array $roles)
    {
        if (auth()->user()?->isAdmin()) {
            $users = User::role($roles)->orderBy('name')->get();
            if (auth()->check() && !$users->contains('id', auth()->id())) {
                $users->prepend(auth()->user());
            }
            return $users;
        }

        return User::where('id', auth()->id())->orderBy('name')->get();
    }

    private function visibleCustomers()
    {
        return $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        abort_unless(Setting::query()->where('key', 'google_connection_enabled')->value('value') === '1', 404);
        $previousUrl = url()->previous();

        if (str_starts_with($previousUrl, url('/'))) {
            session(['google_calendar_redirect_after_auth' => $previousUrl]);
        }

        $googleService = new GoogleCalendarService();
        $authUrl = $googleService->getAuthUrl();

        if (blank($authUrl)) {
            return redirect()->back()->with('error', 'Google Calendar configuration is incomplete. Please check Google client settings.');
        }

        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        abort_unless(Setting::query()->where('key', 'google_connection_enabled')->value('value') === '1', 404);
        $redirectTo = session()->pull('google_calendar_redirect_after_auth', route('profile.show'));

        if ($request->has('code')) {
            $googleService = new GoogleCalendarService();
            $success = $googleService->handleCallback($request->code);

            if ($success) {
                session()->flash('success', 'Google Calendar connected successfully!');
            } else {
                session()->flash('error', 'Failed to connect Google Calendar. Please try again.');
            }
        } elseif ($request->has('error')) {
            session()->flash('error', 'Google Calendar authorization was denied.');
        }

        return redirect($redirectTo);
    }
}
