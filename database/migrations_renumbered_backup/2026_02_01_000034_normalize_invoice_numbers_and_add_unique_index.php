<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeInvoiceNumbers();

        if (!$this->hasInvoiceNumberUniqueIndex()) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique('number');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            try {
                $table->dropUnique('invoices_number_unique');
            } catch (\Throwable $e) {
            }
        });
    }

    private function normalizeInvoiceNumbers(): void
    {
        $invoices = DB::table('invoices')
            ->select('id', 'number', 'created_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $next = 1;

        foreach ($invoices as $invoice) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update([
                    'number' => 'INV-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT),
                ]);

            $next++;
        }
    }

    private function hasInvoiceNumberUniqueIndex(): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?',
            [$database, 'invoices', 'invoices_number_unique']
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
