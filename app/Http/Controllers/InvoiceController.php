<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Invoice::class);
        return view('crm.invoices.index');
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);
        $customers = $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $products = Product::orderBy('name')->get();
        return view('crm.invoices.create', compact('customers', 'currencies', 'products'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->load('items');
        $customers = $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $products = Product::orderBy('name')->get();
        return view('crm.invoices.edit', compact('invoice', 'customers', 'currencies', 'products'));
    }

    // status update
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $request->validate([
            'status' => 'required|in:paid,unpaid'
        ]);

        $invoice->update([
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $invoice->status,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'items', 'creator']);
        return view('crm.invoices.show', compact('invoice'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $fileName = 'Invoice_' . date('Y-m-d_H-i-s') . '.csv';
        $query = Invoice::with(['customer', 'currency']);

        $invoices = $query->latest('invoice_date')->get();

        $fileName = 'invoices_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['ID', 'Invoice #', 'Customer', 'Invoice Date', 'Due Date', 'Amount', 'Status', 'Currency'];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->id,
                    $invoice->number,
                    $invoice->customer?->name,
                    optional($invoice->invoice_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    $invoice->total_amount,
                    ucfirst($invoice->status ?? 'unpaid'),
                    $invoice->currency->code,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'currency', 'items', 'creator']);

        $pdf = Pdf::loadView('crm.invoices.pdf', compact('invoice'));

        $filename = 'invoice-' . ($invoice->number ?: $invoice->id) . '.pdf';
        return $pdf->download($filename);
    }
}
