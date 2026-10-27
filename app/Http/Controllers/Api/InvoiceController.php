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

        $query = Invoice::with(['customer', 'creator', 'items']);
        
        // Apply explicit ownership filter for non-admin users
        // Note: Invoice model also has a global scope, but this is a safety measure
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // ✅ ADVANCED SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
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
        $validator = Validator::make(
            $request->all(),
            [
                'customer_id' => ['required', 'integer', 'exists:customers,id'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
                'currency_id' => ['required', 'integer', 'exists:currencies,id'],
                'comment' => ['nullable', 'string', 'max:2000'],
                'number' => ['nullable', 'string', 'max:255', 'unique:invoices,number'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
                'items.*.amount' => ['required', 'numeric'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ],
            [
                'customer_id.required' => 'Customer is required.',
                'customer_id.integer' => 'Customer must be an integer.',
                'customer_id.exists' => 'Customer does not exist.',
                'invoice_date.required' => 'Invoice date is required.',
                'invoice_date.date' => 'Invoice date must be a valid date.',
                'due_date.required' => 'Due date is required.',
                'due_date.date' => 'Due date must be a valid date.',
                'due_date.after_or_equal' => 'Due date must be after or equal to the invoice date.',
                'currency_id.required' => 'Currency is required.',
                'currency_id.integer' => 'Currency must be an integer.',
                'currency_id.exists' => 'Currency does not exist.',
                'comment.string' => 'Comment must be a string.',
                'comment.max' => 'Comment must not be greater than 2000 characters.',
                'number.string' => 'Invoice number must be a valid text value.',
                'number.max' => 'Number must not be greater than 255.',
                'number.unique' => 'Invoice number already exists.',
                'items.required' => 'Items are required.',
                'items.array' => 'Items must be an array.',
                'items.min' => 'Items must have at least 1 item.',
                'items.*.product_id.required' => 'Product is required.',
                'items.*.product_id.integer' => 'Product must be an integer.',
                'items.*.product_id.exists' => 'Product does not exist.',
                'items.*.amount.required' => 'Amount is required.',
                'items.*.amount.numeric' => 'Amount must be a numeric.',
                'items.*.amount.min' => 'Amount must be at least 0.',
                'items.*.quantity.required' => 'Quantity is required.',
                'items.*.quantity.integer' => 'Quantity must be an integer.',
                'items.*.quantity.min' => 'Quantity must be at least 1.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $this->ensureVisibleCustomer((int) $data['customer_id']);
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
            if (Invoice::supportsOwnedByUserColumn()) {
                $data['user_id'] = auth()->id();
            }

            if (empty($data['number'])) {
                $data['number'] = $this->generateNextInvoiceNumber();
            }

            $itemsData = $data['items'];
            unset($data['items']);

            $products = Product::query()
                ->whereIn('id', collect($itemsData)->pluck('product_id')->unique()->all())
                ->get()
                ->keyBy('id');

            $totalAmount = 0;
            foreach ($itemsData as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) {
                    throw new \RuntimeException('One or more invoice products could not be found.');
                }

                $totalAmount += $product->price * $item['quantity'];
            }
            $data['total_amount'] = $totalAmount;

            $invoice = Invoice::create($data);

            foreach ($itemsData as $item) {
                $product = $products->get($item['product_id']);

                $invoice->items()->create([
                    'product_name' => $product->name,
                    'amount' => $product->price,
                    'quantity' => $item['quantity'],
                    'total_price' => $product->price * $item['quantity'],
                    'product_id' => $item['product_id'] ?? null,
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
        $invoice->load(['customer', 'items', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Invoice retrieved successfully'
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'customer_id' => ['required', 'integer', 'exists:customers,id'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
                'currency_id' => ['required', 'integer', 'exists:currencies,id'],
                'comment' => ['nullable', 'string', 'max:2000'],
                'number' => ['nullable', 'string', 'max:255', 'unique:invoices,number,' . $invoice->id],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
                'items.*.amount' => ['required', 'numeric', 'min:0'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
            ],
            [
                'customer_id.required' => 'Customer is required.',
                'customer_id.integer' => 'Customer must be an integer.',
                'customer_id.exists' => 'Customer does not exist.',
                'invoice_date.required' => 'Invoice date is required.',
                'invoice_date.date' => 'Invoice date must be a valid date.',
                'due_date.required' => 'Due date is required.',
                'due_date.date' => 'Due date must be a valid date.',
                'due_date.after_or_equal' => 'Due date must be after or equal to the invoice date.',
                'currency_id.required' => 'Currency is required.',
                'currency_id.integer' => 'Currency must be an integer.',
                'currency_id.exists' => 'Currency does not exist.',
                'comment.string' => 'Comment must be a string.',
                'comment.max' => 'Comment must not be greater than 2000 characters.',
                'number.string' => 'Invoice number must be a valid text value.',
                'number.max' => 'Number must not be greater than 255.',
                'number.unique' => 'Invoice number already exists.',
                'items.required' => 'Items are required.',
                'items.array' => 'Items must be an array.',
                'items.min' => 'Items must have at least 1 item.',
                'items.*.product_id.required' => 'Product is required.',
                'items.*.product_id.integer' => 'Product must be an integer.',
                'items.*.product_id.exists' => 'Product does not exist.',
                'items.*.amount.required' => 'Amount is required.',
                'items.*.amount.numeric' => 'Amount must be a numeric.',
                'items.*.amount.min' => 'Amount must be at least 0.',
                'items.*.quantity.required' => 'Quantity is required.',
                'items.*.quantity.integer' => 'Quantity must be an integer.',
                'items.*.quantity.min' => 'Quantity must be at least 1.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $this->ensureVisibleCustomer((int) $data['customer_id']);
            $data['updated_by'] = auth()->id();
            if (Invoice::supportsOwnedByUserColumn()) {
                $data['user_id'] = $invoice->user_id ?? auth()->id();
            }

            $itemsData = $data['items'];
            unset($data['items']);

            $products = Product::query()
                ->whereIn('id', collect($itemsData)->pluck('product_id')->unique()->all())
                ->get()
                ->keyBy('id');

            $totalAmount = 0;
            foreach ($itemsData as $item) {
                $product = $products->get($item['product_id']);
                if (!$product) {
                    throw new \RuntimeException('One or more invoice products could not be found.');
                }

                $totalAmount += $product->price * $item['quantity'];
            }
            $data['total_amount'] = $totalAmount;

            $invoice->update($data);

            // Replace items
            $invoice->items()->delete();
            foreach ($itemsData as $item) {
                $product = $products->get($item['product_id']);

                $invoice->items()->create([
                    'product_name' => $product->name,
                    'amount' => $product->price,
                    'quantity' => $item['quantity'],
                    'total_price' => $product->price * $item['quantity'],
                    'product_id' => $item['product_id'] ?? null,
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
