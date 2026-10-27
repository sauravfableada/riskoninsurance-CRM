<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();

                $table->string('booking_no')->unique();

                // Soft relations (no FK constraints to avoid MySQL issues)
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->unsignedBigInteger('quotation_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('agent_id')->nullable();
                $table->unsignedBigInteger('tour_package_id')->nullable();
                $table->unsignedBigInteger('currency_id')->nullable();

                $table->date('travel_start_date')->nullable();
                $table->date('travel_end_date')->nullable();
                $table->unsignedInteger('adults')->default(1);
                $table->unsignedInteger('children')->default(0);
                $table->unsignedInteger('rooms')->default(0);

                $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);

                $table->timestamps();

                $table->index(
                    ['lead_id', 'quotation_id', 'customer_id', 'agent_id', 'tour_package_id', 'currency_id', 'status'],
                    'bookings_rel_status_idx'
                );
            });

            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(
                ['lead_id', 'quotation_id', 'customer_id', 'agent_id', 'tour_package_id', 'currency_id', 'status'],
                'bookings_rel_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

