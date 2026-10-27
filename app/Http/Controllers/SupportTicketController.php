<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index()
    {
        return view('crm.tickets.index');
    }

    public function create()
    {
        $customers = Customer::query()->where('user_id', auth()->id())->orderBy('name')->get();
        $bookings = Booking::orderBy('booking_no')->get();

        return view('crm.tickets.create', compact('customers', 'bookings'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer', 'creator', 'statusHistories.updater']);

        return view('crm.tickets.show', compact('ticket'));
    }

    public function edit(SupportTicket $ticket)
    {
        $ticket->load(['statusHistories.updater']);
        $customers = Customer::query()->where('user_id', auth()->id())->orderBy('name')->get();
        $bookings = Booking::orderBy('booking_no')->get();

        return view('crm.tickets.edit', compact('ticket', 'customers', 'bookings'));
    }

    public function export(Request $request)
    {
        $fileName = 'tickets_' . date('Y-m-d_H-i-s') . '.csv';

        $query = SupportTicket::with(['customer', 'creator'])
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('ticket_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $tickets = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Customer Name', 'Ticket Name', 'Priority', 'Status', 'Created By', 'Created At'];

        $callback = function () use ($tickets, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;

            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $i++,
                    $ticket->customer?->name ?? '--',
                    $ticket->ticket_name ?? '--',
                    $ticket->priority ?? '--',
                    $ticket->status ?? '--',
                    $ticket->creator?->name ?? '--',
                    $ticket->created_at
                        ? $ticket->created_at->timezone('Asia/Kolkata')->format('d-m-Y h:i A')
                        : '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
