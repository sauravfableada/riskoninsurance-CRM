<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create($data);

        // Update Invoice status
        $invoice = Invoice::findOrFail($data['invoice_id']);
        $totalPaid = $invoice->payments()->sum('amount');

        if ($totalPaid >= $invoice->grand_total) {
            $invoice->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partial']);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;
        $payment->delete();

        // Re-update status
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid == 0) {
            $invoice->update(['status' => 'unpaid']);
        } elseif ($totalPaid < $invoice->grand_total) {
            $invoice->update(['status' => 'partial']);
        }

        return redirect()->back()->with('success', 'Payment deleted and status updated.');
    }
}
