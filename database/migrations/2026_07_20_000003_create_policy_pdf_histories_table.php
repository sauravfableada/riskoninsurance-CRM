<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('policy_pdf_histories')) {
            Schema::create('policy_pdf_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('policy_id')->index();
                $table->string('file_name');
                $table->string('original_file_name')->nullable();
                $table->string('document_type')->default('policy');
                $table->string('label')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        DB::table('policies')
            ->whereNotNull('policy_pdf')
            ->where('policy_pdf', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($policies) {
                $rows = [];
                foreach ($policies as $policy) {
                    $isRenewal = !empty(data_get($policy, 'renewal_of_policy_id'))
                        || (!empty(data_get($policy, 'renew_id')) && (int) data_get($policy, 'renew_id') > 0)
                        || strtolower((string) data_get($policy, 'renew_status', '')) === 'renewed';
                    if (DB::table('policy_pdf_histories')
                        ->where('policy_id', $policy->id)
                        ->where('file_name', basename($policy->policy_pdf))
                        ->exists()) {
                        continue;
                    }
                    $rows[] = [
                        'policy_id' => $policy->id,
                        'file_name' => basename($policy->policy_pdf),
                        'original_file_name' => basename($policy->policy_pdf),
                        'document_type' => $isRenewal ? 'renewal' : 'policy',
                        'label' => $isRenewal ? 'Renewed Policy PDF' : 'Original Policy PDF',
                        'uploaded_by' => $policy->created_by ?? null,
                        'created_at' => $policy->created_at ?? now(),
                        'updated_at' => $policy->updated_at ?? now(),
                    ];
                }
                DB::table('policy_pdf_histories')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_pdf_histories');
    }
};
