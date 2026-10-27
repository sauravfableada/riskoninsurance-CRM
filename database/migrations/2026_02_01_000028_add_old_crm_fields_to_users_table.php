<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp', 50)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('users', 'whatsapp')) {
                $dropColumns[] = 'whatsapp';
            }

            if (Schema::hasColumn('users', 'address')) {
                $dropColumns[] = 'address';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
