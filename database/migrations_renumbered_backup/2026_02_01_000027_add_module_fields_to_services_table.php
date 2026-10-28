<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('services', 'service_name')) {
                $table->string('service_name')->nullable()->after('product_id');
            }

            if (!Schema::hasColumn('services', 'service_price')) {
                $table->decimal('service_price', 10, 2)->default(0)->after('description');
            }

            if (!Schema::hasColumn('services', 'status')) {
                $table->string('status')->default('active')->after('service_price');
            }

            if (!Schema::hasColumn('services', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('services', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('services', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
        });

        if (Schema::hasColumn('services', 'name') && Schema::hasColumn('services', 'service_name')) {
            DB::table('services')
                ->whereNull('service_name')
                ->update(['service_name' => DB::raw('name')]);
        }

        if (Schema::hasColumn('services', 'price') && Schema::hasColumn('services', 'service_price')) {
            DB::table('services')
                ->where('service_price', 0)
                ->update(['service_price' => DB::raw('price')]);
        }

        if (Schema::hasColumn('services', 'status')) {
            DB::table('services')
                ->whereNull('status')
                ->update(['status' => 'active']);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['product_id', 'service_name', 'service_price', 'status', 'created_by', 'updated_by', 'deleted_by'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
