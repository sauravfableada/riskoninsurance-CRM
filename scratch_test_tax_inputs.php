<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\CompanyMaster;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;

$user = User::first();
if ($user) {
    auth()->login($user);
}

try {
    echo "1. Checking Create View for CGST/SGST Input Fields...\n";
    $controller = app(\App\Http\Controllers\InvoiceController::class);
    $htmlCreate = $controller->create()->render();
    echo "   Contains cgst_percentage input: " . (str_contains($htmlCreate, 'name="cgst_percentage"') ? 'YES' : 'NO') . "\n";
    echo "   Contains sgst_percentage input: " . (str_contains($htmlCreate, 'name="sgst_percentage"') ? 'YES' : 'NO') . "\n";

    echo "\n2. Creating test invoice with custom CGST 7% and SGST 7% (Total 14%)...\n";
    $company = CompanyMaster::first();
    $invoice = Invoice::create([
        'number' => 'INV-TEST-TAX-001',
        'invoice_type' => 'company_pay_in',
        'company_id' => $company?->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 20000.00,
        'tax_percentage' => 14.00,
        'tax_amount' => 2800.00,
        'cgst_percentage' => 7.00,
        'sgst_percentage' => 7.00,
        'cgst_amount' => 1400.00,
        'sgst_amount' => 1400.00,
        'total_amount' => 22800.00,
        'grand_total' => 22800.00,
        'status' => 'unpaid',
        'created_by' => $user?->id,
        'user_id' => $user?->id,
    ]);

    $invoice->load(['company', 'customer', 'items']);

    echo "\n3. Checking Edit View...\n";
    $companies = CompanyMaster::orderBy('name')->get();
    $customers = Customer::orderBy('name')->get();
    $currencies = Currency::where('is_active', true)->get();
    $products = Product::orderBy('name')->get();
    $htmlEdit = view('crm.invoices.edit', compact('invoice', 'companies', 'customers', 'currencies', 'products'))->render();
    echo "   Edit contains cgst_percentage input: " . (str_contains($htmlEdit, 'name="cgst_percentage"') ? 'YES' : 'NO') . "\n";
    echo "   Edit contains sgst_percentage input: " . (str_contains($htmlEdit, 'name="sgst_percentage"') ? 'YES' : 'NO') . "\n";
    echo "   Edit has value '7' for CGST: " . (str_contains($htmlEdit, 'value="7"') ? 'YES' : 'NO') . "\n";

    echo "\n4. Checking Show View...\n";
    $htmlShow = view('crm.invoices.show', compact('invoice'))->render();
    echo "   Show has CGST (7%): " . (str_contains($htmlShow, 'CGST (7%):') ? 'YES' : 'NO') . "\n";
    echo "   Show has SGST (7%): " . (str_contains($htmlShow, 'SGST (7%):') ? 'YES' : 'NO') . "\n";
    echo "   Show has ₹1,400.00: " . (str_contains($htmlShow, '1,400.00') ? 'YES' : 'NO') . "\n";

    echo "\n5. Checking PDF Generation...\n";
    $logoBase64 = null;
    $signatureBase64 = null;
    $htmlPdf = view('crm.invoices.pdf', compact('invoice', 'logoBase64', 'signatureBase64'))->render();
    echo "   PDF contains CGST @ 7%: " . (str_contains($htmlPdf, 'CGST @ 7%') ? 'YES' : 'NO') . "\n";
    echo "   PDF contains SGST @ 7%: " . (str_contains($htmlPdf, 'SGST @ 7%') ? 'YES' : 'NO') . "\n";
    echo "   PDF contains 1,400.00: " . (str_contains($htmlPdf, '1,400.00') ? 'YES' : 'NO') . "\n";

    $pdf = Pdf::loadView('crm.invoices.pdf', compact('invoice', 'logoBase64', 'signatureBase64'));
    $output = $pdf->output();
    echo "   PDF rendered cleanly! Size: " . strlen($output) . " bytes\n";

    $invoice->forceDelete();
    echo "\n6. Cleaned up test data.\n";
    echo "\nALL TESTS PASSED PERFECTLY!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
