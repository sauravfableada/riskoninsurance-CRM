<?php

namespace App\Http\Controllers;

use App\Models\CompanyMaster;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Policy;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $companies = CompanyMaster::orderBy('name')->get();
        $totalInvoiced = (float) Invoice::sum(DB::raw('COALESCE(NULLIF(grand_total, 0), total_amount)'));
        $paidInvoiced = (float) Invoice::where('status', 'paid')->sum(DB::raw('COALESCE(NULLIF(grand_total, 0), total_amount)'));
        $pendingInvoiced = (float) Invoice::where('status', 'unpaid')->sum(DB::raw('COALESCE(NULLIF(grand_total, 0), total_amount)'));
        $companyPayInCount = Invoice::where('invoice_type', 'company_pay_in')->count();

        return view('crm.invoices.index', compact(
            'companies',
            'totalInvoiced',
            'paidInvoiced',
            'pendingInvoiced',
            'companyPayInCount'
        ));
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);

        $companies = CompanyMaster::orderBy('name')->get();
        $customers = $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $products = Product::orderBy('name')->get();

        $defaultAccount = [
            'name' => 'My Billing Account',
            'id_code' => '01E2F9-74AFE8-B222F4',
            'type' => 'Direct',
            'organization' => 'Riskon Insurance',
        ];

        // Preview next invoice number
        $lastInvoice = Invoice::orderByDesc('id')->first();
        $nextNum = $lastInvoice ? ((int) preg_replace('/[^0-9]/', '', substr($lastInvoice->number ?? '', -4)) + 1) : 1;
        $nextInvoiceNumber = 'INV-PAYIN-' . date('Ymd') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return view('crm.invoices.create', compact(
            'companies',
            'customers',
            'currencies',
            'products',
            'defaultAccount',
            'nextInvoiceNumber'
        ));
    }

    /**
     * AJAX endpoint to fetch unbilled/pending policies of a specific insurance company for Pay-In invoicing.
     */
    public function getCompanyPolicies(Request $request, $companyId)
    {
        // When editing an invoice, allow policies from that specific invoice to remain available
        $excludeInvoiceId = $request->query('invoice_id');

        // Find policy IDs and numbers already billed in any active invoice (paid, unpaid, sent)
        $invoicedItems = InvoiceItem::whereHas('invoice', function ($q) use ($excludeInvoiceId) {
            $q->where('status', '!=', 'cancelled');
            if ($excludeInvoiceId) {
                $q->where('id', '!=', $excludeInvoiceId);
            }
        })->get(['policy_id', 'policy_number', 'product_name']);

        $billedPolicyIds = [];
        $billedPolicyNumbers = [];

        foreach ($invoicedItems as $item) {
            if (!empty($item->policy_id)) {
                $billedPolicyIds[] = (int) $item->policy_id;
            }
            if (!empty($item->policy_number)) {
                $billedPolicyNumbers[] = trim($item->policy_number);
            }
            // Also extract policy number if formatted in product_name e.g. "Pay-In Commission (POL-20261030-0001 - Test-Holder)"
            if (!empty($item->product_name) && preg_match('/POL-[0-9]{8}-[0-9]+/i', $item->product_name, $matches)) {
                $billedPolicyNumbers[] = trim($matches[0]);
            }
        }
        $billedPolicyIds = array_unique(array_filter($billedPolicyIds));
        $billedPolicyNumbers = array_unique(array_filter($billedPolicyNumbers));

        $query = Policy::with(['holder', 'policyType', 'latestCommissionEntry'])
            ->where('company_id', $companyId);

        if (!empty($billedPolicyIds)) {
            $query->whereNotIn('id', $billedPolicyIds);
        }
        if (!empty($billedPolicyNumbers)) {
            $query->whereNotIn('policy_number', $billedPolicyNumbers);
        }

        $policies = $query->latest('id')->get();

        $data = $policies->map(function ($policy) {
            $premium = (float) ($policy->premium_amount ?? 0);
            $entry = $policy->latestCommissionEntry;
            $payInPct = (float) ($entry?->pay_in_percentage ?? ($policy->pay_in_percentage ?? 20));
            $payInAmt = (float) ($entry?->pay_in_amount ?? ($policy->pay_in_amount ?? round(($premium * $payInPct) / 100, 2)));

            return [
                'id' => $policy->id,
                'policy_number' => $policy->policy_number ?: ('#' . $policy->id),
                'holder_name' => $policy->holder?->name ?? 'Direct Customer',
                'policy_type_name' => $policy->policyType?->name ?? 'General Insurance',
                'premium_amount' => $premium,
                'pay_in_percentage' => $payInPct,
                'pay_in_amount' => $payInAmt,
                'start_date' => $policy->start_date ? date('d-m-Y', strtotime($policy->start_date)) : '—',
            ];
        });

        return response()->json([
            'status' => true,
            'policies' => $data,
        ]);
    }

    /**
     * AJAX endpoint to fetch unbilled/active policies of a specific customer for customer invoicing.
     */
    public function getCustomerPolicies(Request $request, $customerId)
    {
        $excludeInvoiceId = $request->query('invoice_id');

        $invoicedItems = InvoiceItem::whereHas('invoice', function ($q) use ($excludeInvoiceId) {
            $q->where('status', '!=', 'cancelled');
            if ($excludeInvoiceId) {
                $q->where('id', '!=', $excludeInvoiceId);
            }
        })->get(['policy_id', 'policy_number', 'product_name']);

        $billedPolicyIds = [];
        $billedPolicyNumbers = [];

        foreach ($invoicedItems as $item) {
            if (!empty($item->policy_id)) {
                $billedPolicyIds[] = (int) $item->policy_id;
            }
            if (!empty($item->policy_number)) {
                $billedPolicyNumbers[] = trim($item->policy_number);
            }
            if (!empty($item->product_name) && preg_match('/POL-[0-9]{8}-[0-9]+/i', $item->product_name, $matches)) {
                $billedPolicyNumbers[] = trim($matches[0]);
            }
        }
        $billedPolicyIds = array_unique(array_filter($billedPolicyIds));
        $billedPolicyNumbers = array_unique(array_filter($billedPolicyNumbers));

        $query = Policy::with(['company', 'policyType'])
            ->where('holder_id', $customerId);

        if (!empty($billedPolicyIds)) {
            $query->whereNotIn('id', $billedPolicyIds);
        }
        if (!empty($billedPolicyNumbers)) {
            $query->whereNotIn('policy_number', $billedPolicyNumbers);
        }

        $policies = $query->latest('id')->get();

        $data = $policies->map(function ($policy) {
            $premium = (float) ($policy->premium_amount ?? 0);

            return [
                'id' => $policy->id,
                'policy_number' => $policy->policy_number ?: ('#' . $policy->id),
                'company_name' => $policy->company?->name ?? 'Insurance Company',
                'policy_type_name' => $policy->policyType?->name ?? 'General Insurance',
                'premium_amount' => $premium,
                'start_date' => $policy->start_date ? date('d-m-Y', strtotime($policy->start_date)) : '—',
            ];
        });

        return response()->json([
            'status' => true,
            'policies' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $invoiceType = $request->input('invoice_type', 'company_pay_in');

        $rules = [
            'invoice_type'   => 'required|in:company_pay_in,customer',
            'invoice_date'   => 'required|date',
            'due_date'       => 'nullable|date',
            'currency_id'    => 'nullable|exists:currencies,id',
            'comment'        => 'nullable|string|max:2000',
            'terms_and_conditions' => 'nullable|string|max:5000',
            'number'         => 'nullable|string|max:255|unique:invoices,number',
            'account_name'   => 'nullable|string|max:255',
            'account_id_code'=> 'nullable|string|max:255',
            'account_type'   => 'nullable|string|max:255',
            'organization'   => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0',
            'cgst_percentage' => 'nullable|numeric|min:0',
            'sgst_percentage' => 'nullable|numeric|min:0',
            'igst_percentage' => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
        ];

        if ($invoiceType === 'company_pay_in') {
            $rules['company_id'] = 'required|exists:company_master,id';
        } else {
            $rules['customer_id'] = 'required|exists:customers,id';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            // Auto-generate invoice number if not passed
            $number = $validated['number'] ?? null;
            if (empty($number)) {
                $prefix = ($invoiceType === 'company_pay_in') ? 'INV-PAYIN-' : 'INV-';
                $lastInv = Invoice::orderByDesc('id')->first();
                $seq = $lastInv ? ($lastInv->id + 1) : 1;
                $number = $prefix . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
            }

            // Calculate Subtotal from line items
            $itemsData = $request->input('items', []);
            $subtotal = 0;
            foreach ($itemsData as $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $amount = (float) ($item['amount'] ?? ($item['pay_in_amount'] ?? 0));
                $subtotal += round($amount * $qty, 2);
            }

            $taxPct = (float) ($validated['tax_percentage'] ?? 0);
            $cgstPct = isset($validated['cgst_percentage']) ? (float) $validated['cgst_percentage'] : round($taxPct / 2, 2);
            $sgstPct = isset($validated['sgst_percentage']) ? (float) $validated['sgst_percentage'] : round($taxPct - $cgstPct, 2);
            $igstPct = (float) ($validated['igst_percentage'] ?? 0);

            $cgstAmount = round(($subtotal * $cgstPct) / 100, 2);
            $sgstAmount = round(($subtotal * $sgstPct) / 100, 2);
            $igstAmount = round(($subtotal * $igstPct) / 100, 2);
            $taxAmount = round($cgstAmount + $sgstAmount + $igstAmount, 2);
            $grandTotal = round($subtotal + $taxAmount, 2);
            $totalTaxPct = round($cgstPct + $sgstPct + $igstPct, 2);

            $defaultCurrency = Currency::where('code', 'INR')->first() ?? Currency::first();

            $invoice = Invoice::create([
                'number'          => $number,
                'invoice_type'    => $invoiceType,
                'company_id'      => $validated['company_id'] ?? null,
                'customer_id'     => $validated['customer_id'] ?? null,
                'account_name'    => $validated['account_name'] ?? 'My Billing Account',
                'account_id_code' => $validated['account_id_code'] ?? '01E2F9-74AFE8-B222F4',
                'account_type'    => $validated['account_type'] ?? 'Direct',
                'organization'    => $validated['organization'] ?? 'Riskon Insurance',
                'currency_id'     => $validated['currency_id'] ?? $defaultCurrency?->id,
                'invoice_date'    => $validated['invoice_date'],
                'due_date'        => $validated['due_date'] ?? null,
                'subtotal'        => $subtotal,
                'tax_percentage'  => $totalTaxPct,
                'tax_amount'      => $taxAmount,
                'cgst_percentage' => $cgstPct,
                'sgst_percentage' => $sgstPct,
                'igst_percentage' => $igstPct,
                'cgst_amount'     => $cgstAmount,
                'sgst_amount'     => $sgstAmount,
                'igst_amount'     => $igstAmount,
                'total_amount'    => $grandTotal,
                'grand_total'     => $grandTotal,
                'status'          => $request->input('status', 'unpaid'),
                'comment'         => $validated['comment'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'created_by'      => auth()->id(),
                'user_id'         => auth()->id(),
            ]);

            // Create Invoice Items
            foreach ($itemsData as $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $amount = (float) ($item['amount'] ?? ($item['pay_in_amount'] ?? 0));
                $lineTotal = round($amount * $qty, 2);

                $productName = $item['product_name'] ?? ($item['description'] ?? null);
                if (empty($productName)) {
                    $productName = !empty($item['policy_number'])
                        ? ('Pay-In Commission for Policy ' . $item['policy_number'])
                        : 'Commission Remittance';
                }

                InvoiceItem::create([
                    'invoice_id'        => $invoice->id,
                    'policy_id'         => $item['policy_id'] ?? null,
                    'policy_number'     => $item['policy_number'] ?? null,
                    'holder_name'       => $item['holder_name'] ?? null,
                    'policy_type_name'  => $item['policy_type_name'] ?? null,
                    'gross_premium'     => !empty($item['gross_premium']) ? (float) $item['gross_premium'] : null,
                    'pay_in_percentage' => !empty($item['pay_in_percentage']) ? (float) $item['pay_in_percentage'] : null,
                    'pay_in_amount'     => $amount,
                    'product_id'        => $item['product_id'] ?? null,
                    'product_name'      => $productName,
                    'hsn_sac'           => !empty($item['hsn_sac']) ? trim($item['hsn_sac']) : '997161',
                    'amount'            => $amount,
                    'quantity'          => $qty,
                    'total_price'       => $lineTotal,
                    'created_by'        => auth()->id(),
                ]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Invoice created successfully!',
                    'data'     => $invoice->load(['items', 'company', 'customer']),
                    'redirect' => route('invoices.show', $invoice->id),
                ]);
            }

            return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice created successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice store failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create invoice: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['company', 'customer', 'items.policy', 'creator', 'currency']);
        return view('crm.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->load(['items', 'company', 'customer']);

        $companies = CompanyMaster::orderBy('name')->get();
        $customers = $this->scopeOwnedRecords(Customer::query())->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $products = Product::orderBy('name')->get();

        return view('crm.invoices.edit', compact('invoice', 'companies', 'customers', 'currencies', 'products'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $rules = [
            'invoice_type'   => 'required|in:company_pay_in,customer',
            'invoice_date'   => 'required|date',
            'due_date'       => 'nullable|date',
            'currency_id'    => 'nullable|exists:currencies,id',
            'comment'        => 'nullable|string|max:2000',
            'terms_and_conditions' => 'nullable|string|max:5000',
            'number'         => 'nullable|string|max:255|unique:invoices,number,' . $invoice->id,
            'account_name'   => 'nullable|string|max:255',
            'account_id_code'=> 'nullable|string|max:255',
            'account_type'   => 'nullable|string|max:255',
            'organization'   => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0',
            'cgst_percentage' => 'nullable|numeric|min:0',
            'sgst_percentage' => 'nullable|numeric|min:0',
            'igst_percentage' => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
        ];

        if ($request->input('invoice_type') === 'company_pay_in') {
            $rules['company_id'] = 'required|exists:company_master,id';
        } else {
            $rules['customer_id'] = 'required|exists:customers,id';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $itemsData = $request->input('items', []);
            $subtotal = 0;
            foreach ($itemsData as $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $amount = (float) ($item['amount'] ?? ($item['pay_in_amount'] ?? 0));
                $subtotal += round($amount * $qty, 2);
            }

            $taxPct = (float) ($validated['tax_percentage'] ?? 0);
            $cgstPct = isset($validated['cgst_percentage']) ? (float) $validated['cgst_percentage'] : round($taxPct / 2, 2);
            $sgstPct = isset($validated['sgst_percentage']) ? (float) $validated['sgst_percentage'] : round($taxPct - $cgstPct, 2);
            $igstPct = (float) ($validated['igst_percentage'] ?? 0);

            $cgstAmount = round(($subtotal * $cgstPct) / 100, 2);
            $sgstAmount = round(($subtotal * $sgstPct) / 100, 2);
            $igstAmount = round(($subtotal * $igstPct) / 100, 2);
            $taxAmount = round($cgstAmount + $sgstAmount + $igstAmount, 2);
            $grandTotal = round($subtotal + $taxAmount, 2);
            $totalTaxPct = round($cgstPct + $sgstPct + $igstPct, 2);

            $invoice->update([
                'number'          => $validated['number'] ?: $invoice->number,
                'invoice_type'    => $validated['invoice_type'],
                'company_id'      => $validated['company_id'] ?? null,
                'customer_id'     => $validated['customer_id'] ?? null,
                'account_name'    => $validated['account_name'] ?? $invoice->account_name,
                'account_id_code' => $validated['account_id_code'] ?? $invoice->account_id_code,
                'account_type'    => $validated['account_type'] ?? $invoice->account_type,
                'organization'    => $validated['organization'] ?? $invoice->organization,
                'currency_id'     => $validated['currency_id'] ?? $invoice->currency_id,
                'invoice_date'    => $validated['invoice_date'],
                'due_date'        => $validated['due_date'] ?? null,
                'subtotal'        => $subtotal,
                'tax_percentage'  => $totalTaxPct,
                'tax_amount'      => $taxAmount,
                'cgst_percentage' => $cgstPct,
                'sgst_percentage' => $sgstPct,
                'igst_percentage' => $igstPct,
                'cgst_amount'     => $cgstAmount,
                'sgst_amount'     => $sgstAmount,
                'igst_amount'     => $igstAmount,
                'total_amount'    => $grandTotal,
                'grand_total'     => $grandTotal,
                'status'          => $request->input('status', $invoice->status),
                'comment'         => $validated['comment'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'updated_by'      => auth()->id(),
            ]);

            // Re-sync items
            $invoice->items()->delete();
            foreach ($itemsData as $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $amount = (float) ($item['amount'] ?? ($item['pay_in_amount'] ?? 0));
                $lineTotal = round($amount * $qty, 2);

                $productName = $item['product_name'] ?? ($item['description'] ?? null);
                if (empty($productName)) {
                    $productName = !empty($item['policy_number'])
                        ? ('Pay-In Commission for Policy ' . $item['policy_number'])
                        : 'Commission Remittance';
                }

                InvoiceItem::create([
                    'invoice_id'        => $invoice->id,
                    'policy_id'         => $item['policy_id'] ?? null,
                    'policy_number'     => $item['policy_number'] ?? null,
                    'holder_name'       => $item['holder_name'] ?? null,
                    'policy_type_name'  => $item['policy_type_name'] ?? null,
                    'gross_premium'     => !empty($item['gross_premium']) ? (float) $item['gross_premium'] : null,
                    'pay_in_percentage' => !empty($item['pay_in_percentage']) ? (float) $item['pay_in_percentage'] : null,
                    'pay_in_amount'     => $amount,
                    'product_id'        => $item['product_id'] ?? null,
                    'product_name'      => $productName,
                    'hsn_sac'           => !empty($item['hsn_sac']) ? trim($item['hsn_sac']) : '997161',
                    'amount'            => $amount,
                    'quantity'          => $qty,
                    'total_price'       => $lineTotal,
                    'created_by'        => auth()->id(),
                ]);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Invoice updated successfully!',
                    'data'     => $invoice->fresh(['items', 'company', 'customer']),
                    'redirect' => route('invoices.show', $invoice->id),
                ]);
            }

            return redirect()->route('invoices.show', $invoice->id)->with('success', 'Invoice updated successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice update failed: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update invoice.'], 500);
            }

            return back()->withInput()->with('error', 'Failed to update invoice.');
        }
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $request->validate([
            'status' => 'required|in:paid,unpaid,cancelled'
        ]);

        $invoice->update([
            'status'     => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status'  => $invoice->status,
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->items()->delete();
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully.'
        ]);
    }

    protected function buildFilteredInvoicesQuery(Request $request)
    {
        $user = auth()->user();
        $query = Invoice::with(['company', 'customer', 'currency', 'items']);

        // Apply explicit ownership filter for non-admin users
        if ($user && !$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('account_id_code', 'like', "%{$search}%")
                    ->orWhereHas('company', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->latest('invoice_date')->latest('id');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->buildFilteredInvoicesQuery($request)->get();

        $fileName = 'invoices_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = ['ID', 'Invoice #', 'Type', 'Billed To', 'Account ID', 'Invoice Date', 'Due Date', 'Subtotal', 'Tax', 'Grand Total', 'Status'];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, $columns);

            foreach ($invoices as $inv) {
                $billedTo = ($inv->invoice_type === 'company_pay_in')
                    ? ($inv->company?->name ?? 'Insurance Company')
                    : ($inv->customer?->name ?? 'Customer');

                fputcsv($file, [
                    $inv->id,
                    $inv->number,
                    ($inv->invoice_type === 'company_pay_in') ? 'Company Pay-In' : 'Customer',
                    $billedTo,
                    $inv->account_id_code ?? '—',
                    optional($inv->invoice_date)->format('Y-m-d'),
                    optional($inv->due_date)->format('Y-m-d'),
                    $inv->subtotal,
                    $inv->tax_amount,
                    $inv->grand_total ?: $inv->total_amount,
                    ucfirst($inv->status ?? 'unpaid'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->buildFilteredInvoicesQuery($request)->get();

        $totalSubtotal = (float) $invoices->sum(fn($i) => (float) ($i->subtotal ?: ($i->items->sum('total_price') ?: 0)));
        $totalTax = (float) $invoices->sum(fn($i) => (float) ($i->tax_amount ?: 0));
        $totalAmount = (float) $invoices->sum(fn($i) => (float) ($i->grand_total ?: ($i->total_amount ?: 0)));
        $paidAmount = (float) $invoices->where('status', 'paid')->sum(fn($i) => (float) ($i->grand_total ?: ($i->total_amount ?: 0)));
        $unpaidAmount = (float) $invoices->where('status', 'unpaid')->sum(fn($i) => (float) ($i->grand_total ?: ($i->total_amount ?: 0)));

        // Resolve Company Logo for PDF
        $logoBase64 = null;
        $logoPath = null;
        try {
            $settingPath = \App\Models\Setting::query()->where('key', 'company_logo_path')->value('value');
            if ($settingPath) {
                $candidates = [
                    \Illuminate\Support\Facades\Storage::disk('public')->path($settingPath),
                    storage_path('app/public/' . ltrim($settingPath, '/\\')),
                    storage_path('app/' . ltrim($settingPath, '/\\')),
                    public_path('storage/' . ltrim($settingPath, '/\\')),
                    public_path(ltrim($settingPath, '/\\')),
                ];
                foreach ($candidates as $cand) {
                    if ($cand && file_exists($cand) && !is_dir($cand)) {
                        $logoPath = $cand;
                        break;
                    }
                }
            }
            if (!$logoPath || !file_exists($logoPath)) {
                if (file_exists(public_path('logo/riskon_logo.png'))) {
                    $logoPath = public_path('logo/riskon_logo.png');
                } elseif (file_exists(public_path('logo/fableadcrmLogo.png'))) {
                    $logoPath = public_path('logo/fableadcrmLogo.png');
                }
            }
            if ($logoPath && file_exists($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        } catch (\Throwable $e) {}

        $companyName = \App\Models\Setting::query()->where('key', 'company_name')->value('value') ?: 'RISKON IMF PRIVATE LIMITED';

        $companyFilterName = null;
        if ($request->filled('company_id')) {
            $companyFilterName = \App\Models\CompanyMaster::find($request->company_id)?->name;
        }

        $filters = [
            'type'       => $request->invoice_type,
            'company'    => $companyFilterName,
            'status'     => $request->status,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'search'     => $request->search,
        ];

        $hasFilters = !empty(array_filter($filters));

        $pdf = Pdf::loadView('crm.invoices.export_pdf', compact(
            'invoices',
            'totalSubtotal',
            'totalTax',
            'totalAmount',
            'paidAmount',
            'unpaidAmount',
            'logoBase64',
            'companyName',
            'filters',
            'hasFilters'
        ))->setPaper('a4', 'landscape');

        $filename = 'invoices_report_' . date('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['company', 'customer', 'currency', 'items.policy', 'creator']);

        // Resolve Company Logo for PDF
        $logoBase64 = null;
        $logoPath = null;

        try {
            $settingPath = \App\Models\Setting::query()->where('key', 'company_logo_path')->value('value');
            if ($settingPath) {
                $candidates = [
                    \Illuminate\Support\Facades\Storage::disk('public')->path($settingPath),
                    storage_path('app/public/' . ltrim($settingPath, '/\\')),
                    storage_path('app/' . ltrim($settingPath, '/\\')),
                    public_path('storage/' . ltrim($settingPath, '/\\')),
                    public_path(ltrim($settingPath, '/\\')),
                ];
                foreach ($candidates as $cand) {
                    if ($cand && file_exists($cand) && !is_dir($cand)) {
                        $logoPath = $cand;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        if (!$logoPath || !file_exists($logoPath)) {
            if (file_exists(public_path('logo/riskon_logo.png'))) {
                $logoPath = public_path('logo/riskon_logo.png');
            } elseif (file_exists(public_path('logo/fableadcrmLogo.png'))) {
                $logoPath = public_path('logo/fableadcrmLogo.png');
            }
        }

        if ($logoPath && file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        // Resolve Authorized Signature for PDF
        $signatureBase64 = null;
        $signaturePath = null;

        try {
            $sigSettingPath = \App\Models\Setting::query()->where('key', 'company_signature_path')->value('value');
            if ($sigSettingPath) {
                $candidates = [
                    \Illuminate\Support\Facades\Storage::disk('public')->path($sigSettingPath),
                    storage_path('app/public/' . ltrim($sigSettingPath, '/\\')),
                    storage_path('app/' . ltrim($sigSettingPath, '/\\')),
                    public_path('storage/' . ltrim($sigSettingPath, '/\\')),
                    public_path(ltrim($sigSettingPath, '/\\')),
                ];
                foreach ($candidates as $cand) {
                    if ($cand && file_exists($cand) && !is_dir($cand)) {
                        $signaturePath = $cand;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        if ($signaturePath && file_exists($signaturePath)) {
            $sigMime = mime_content_type($signaturePath) ?: 'image/png';
            $signatureBase64 = 'data:' . $sigMime . ';base64,' . base64_encode(file_get_contents($signaturePath));
        }

        $pdf = Pdf::loadView('crm.invoices.pdf', compact('invoice', 'logoBase64', 'signatureBase64'));
        $filename = 'invoice-' . ($invoice->number ?: $invoice->id) . '.pdf';
        return $pdf->download($filename);
    }
}
