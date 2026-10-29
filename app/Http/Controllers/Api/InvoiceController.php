<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware('can:viewAny,' . Invoice::class)->only('index');
        $this->middleware('can:create,' . Invoice::class)->only('store');
        $this->middleware('can:view,invoice')->only('show');
        $this->middleware('can:update,invoice')->only(['update', 'updateStatus']);
        $this->middleware('can:delete,invoice')->only('destroy');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $query = Invoice::with(['customer', 'company', 'creator', 'items']);
        
        // Apply explicit ownership filter for non-admin users
        // Note: Invoice model also has a global scope, but this is a safety measure
        if (!$user->isAdmin()) {
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

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // ✅ ADVANCED SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('account_id_code', 'like', "%{$search}%")
                    ->orWhereHas('company', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
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

        $invoices = $query
            ->orderByRaw("
                CASE
                    WHEN number REGEXP '^INV-[0-9]+$'
                    THEN CAST(SUBSTRING_INDEX(number, '-', -1) AS UNSIGNED)
                    ELSE 999999999
                END ASC
            ")
            ->orderBy('invoice_date')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Invoices retrieved successfully',
            'data' => $invoices,
        ], 200);
    }

    public function store(Request $request)
    {
        $invoiceType = $request->input('invoice_type', 'company_pay_in');

        $rules = [
            'invoice_type'   => ['required', 'in:company_pay_in,customer'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],
            'currency_id'    => ['nullable', 'integer', 'exists:currencies,id'],
            'comment'        => ['nullable', 'string', 'max:2000'],
            'number'         => ['nullable', 'string', 'max:255', 'unique:invoices,number'],
            'account_name'   => ['nullable', 'string', 'max:255'],
            'account_id_code'=> ['nullable', 'string', 'max:255'],
            'account_type'   => ['nullable', 'string', 'max:255'],
            'organization'   => ['nullable', 'string', 'max:255'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0'],
            'items'          => ['required', 'array', 'min:1'],
        ];

        if ($invoiceType === 'company_pay_in') {
            $rules['company_id'] = ['required', 'integer', 'exists:company_master,id'];
        } else {
            $rules['customer_id'] = ['required', 'integer', 'exists:customers,id'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            if (!empty($data['customer_id'])) {
                $this->ensureVisibleCustomer((int) $data['customer_id']);
            }
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
            if (Invoice::supportsOwnedByUserColumn()) {
                $data['user_id'] = auth()->id();
            }

            if (empty($data['number'])) {
                $prefix = ($invoiceType === 'company_pay_in') ? 'INV-PAYIN-' : 'INV-';
                $lastInv = Invoice::orderByDesc('id')->first();
                $seq = $lastInv ? ($lastInv->id + 1) : 1;
                $data['number'] = $prefix . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
            }

            $itemsData = $data['items'];
            unset($data['items']);

            $subtotal = 0;
            foreach ($itemsData as $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $amount = (float) ($item['amount'] ?? ($item['pay_in_amount'] ?? 0));
                $subtotal += round($amount * $qty, 2);
            }

            $taxPct = (float) ($data['tax_percentage'] ?? 0);
            $taxAmount = round(($subtotal * $taxPct) / 100, 2);
            $grandTotal = round($subtotal + $taxAmount, 2);

            $data['subtotal'] = $subtotal;
            $data['tax_percentage'] = $taxPct;
            $data['tax_amount'] = $taxAmount;
            $data['total_amount'] = $grandTotal;
            $data['grand_total'] = $grandTotal;
            $data['status'] = $request->input('status', 'unpaid');
            $data['account_name'] = $data['account_name'] ?? 'My Billing Account';
            $data['account_id_code'] = $data['account_id_code'] ?? '01E2F9-74AFE8-B222F4';
            $data['account_type'] = $data['account_type'] ?? 'Direct';
            $data['organization'] = $data['organization'] ?? 'Riskon Insurance';

            $invoice = Invoice::create($data);

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

                $invoice->items()->create([
                    'product_name'      => $productName,
                    'amount'            => $amount,
                    'quantity'          => $qty,
                    'total_price'       => $lineTotal,
                    'product_id'        => $item['product_id'] ?? null,
                    'policy_id'         => $item['policy_id'] ?? null,
                    'policy_number'     => $item['policy_number'] ?? null,
                    'holder_name'       => $item['holder_name'] ?? null,
                    'policy_type_name'  => $item['policy_type_name'] ?? null,
                    'gross_premium'     => !empty($item['gross_premium']) ? (float) $item['gross_premium'] : null,
                    'pay_in_percentage' => !empty($item['pay_in_percentage']) ? (float) $item['pay_in_percentage'] : null,
                    'pay_in_amount'     => $amount,
                    'created_by'        => auth()->id(),
                ]);
            }

            app(\App\Services\UserLogService::class)->created($invoice, 'Created an Invoice ' . $invoice->number);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice->load('items'),
                'message' => 'Invoice created successfully',
                'redirect' => route('invoices.index'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice create failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['success' => false, 'message' => 'Unable to create invoice right now.'], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['company', 'customer', 'items.policy', 'creator', 'currency']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $invoiceType = $request->input('invoice_type', $invoice->invoice_type ?? 'company_pay_in');

        $rules = [
            'invoice_type'   => ['required', 'in:company_pay_in,customer'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],
            'currency_id'    => ['nullable', 'integer', 'exists:currencies,id'],
            'comment'        => ['nullable', 'string', 'max:2000'],
            'number'         => ['nullable', 'string', 'max:255', 'unique:invoices,number,' . $invoice->id],
            'account_name'   => ['nullable', 'string', 'max:255'],
            'account_id_code'=> ['nullable', 'string', 'max:255'],
            'account_type'   => ['nullable', 'string', 'max:255'],
            'organization'   => ['nullable', 'string', 'max:255'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0'],
            'items'          => ['required', 'array', 'min:1'],
        ];

        if ($invoiceType === 'company_pay_in') {
            $rules['company_id'] = ['required', 'integer', 'exists:company_master,id'];
        } else {
            $rules['customer_id'] = ['required', 'integer', 'exists:customers,id'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            if (!empty($data['customer_id'])) {
                $this->ensureVisibleCustomer((int) $data['customer_id']);
            }
            $data['updated_by'] = auth()->id();
            if (Invoice::supportsOwnedByUserColumn()) {
                $data['user_id'] = $invoice->user_id ?? auth()->id();
            }

            $itemsData = $data['items'];
            unset($data['items']);

            $subtotal = 0;
            foreach ($itemsData as $item) {
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $amount = (float) ($item['amount'] ?? ($item['pay_in_amount'] ?? 0));
                $subtotal += round($amount * $qty, 2);
            }

            $taxPct = (float) ($data['tax_percentage'] ?? 0);
            $taxAmount = round(($subtotal * $taxPct) / 100, 2);
            $grandTotal = round($subtotal + $taxAmount, 2);

            $data['subtotal'] = $subtotal;
            $data['tax_percentage'] = $taxPct;
            $data['tax_amount'] = $taxAmount;
            $data['total_amount'] = $grandTotal;
            $data['grand_total'] = $grandTotal;

            $invoice->update($data);

            // Replace items
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

                $invoice->items()->create([
                    'product_name'      => $productName,
                    'amount'            => $amount,
                    'quantity'          => $qty,
                    'total_price'       => $lineTotal,
                    'product_id'        => $item['product_id'] ?? null,
                    'policy_id'         => $item['policy_id'] ?? null,
                    'policy_number'     => $item['policy_number'] ?? null,
                    'holder_name'       => $item['holder_name'] ?? null,
                    'policy_type_name'  => $item['policy_type_name'] ?? null,
                    'gross_premium'     => !empty($item['gross_premium']) ? (float) $item['gross_premium'] : null,
                    'pay_in_percentage' => !empty($item['pay_in_percentage']) ? (float) $item['pay_in_percentage'] : null,
                    'pay_in_amount'     => $amount,
                    'created_by'        => auth()->id(),
                ]);
            }

            app(\App\Services\UserLogService::class)->updated($invoice, 'Updated an Invoice ' . ($invoice->number ?: ('ID ' . $invoice->id)));

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $invoice->load('items'),
                'message' => 'Invoice updated successfully',
                'redirect' => route('invoices.index')
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice update failed', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['success' => false, 'message' => 'Unable to update invoice right now.'], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->update(['deleted_by' => auth()->id()]);
        app(\App\Services\UserLogService::class)->deleted($invoice, 'Deleted an Invoice ' . ($invoice->number ?: ('ID ' . $invoice->id)));
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:paid,unpaid'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice->update([
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);
        app(\App\Services\UserLogService::class)->updated($invoice, 'Updated an Invoice ' . ($invoice->number ?: ('ID ' . $invoice->id)));

        return response()->json([
            'success' => true,
            'message' => 'Invoice status updated successfully.',
            'status' => $invoice->status,
        ]);
    }

    private function ensureVisibleCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $this->authorize('view', $customer);
    }

    private function generateNextInvoiceNumber(): string
    {
        $numbers = Invoice::withTrashed()
            ->lockForUpdate()
            ->whereNotNull('number')
            ->pluck('number');

        $nextSequence = $this->extractHighestInvoiceSequence($numbers) + 1;

        return 'INV-' . str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    private function extractHighestInvoiceSequence(Collection $numbers): int
    {
        return $numbers
            ->map(function ($number) {
                if (!is_string($number)) {
                    return null;
                }

                if (!preg_match('/^INV-(\d+)$/i', trim($number), $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter(static fn($value) => $value !== null)
            ->max() ?? 0;
    }
}
