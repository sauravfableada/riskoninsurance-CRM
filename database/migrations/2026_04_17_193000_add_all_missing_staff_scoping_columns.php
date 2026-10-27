<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add all missing user_id and assigned_user_id columns for staff scoping.
     */
    public function up(): void
    {
        // ==========================================
        // ADD user_id COLUMNS
        // ==========================================
        
        // Leads table
        if (Schema::hasTable('leads')) {
            if (!Schema::hasColumn('leads', 'user_id')) {
                Schema::table('leads', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE leads SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Deals table
        if (Schema::hasTable('deals')) {
            if (!Schema::hasColumn('deals', 'user_id')) {
                Schema::table('deals', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE deals SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Tasks table
        if (Schema::hasTable('tasks')) {
            if (!Schema::hasColumn('tasks', 'user_id')) {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE tasks SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Meetings table
        if (Schema::hasTable('meetings')) {
            if (!Schema::hasColumn('meetings', 'user_id')) {
                Schema::table('meetings', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE meetings SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Projects table
        if (Schema::hasTable('projects')) {
            if (!Schema::hasColumn('projects', 'user_id')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE projects SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Follow-ups table
        if (Schema::hasTable('follow_ups')) {
            if (!Schema::hasColumn('follow_ups', 'user_id')) {
                Schema::table('follow_ups', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE follow_ups SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Support tickets table
        if (Schema::hasTable('support_tickets')) {
            if (!Schema::hasColumn('support_tickets', 'user_id')) {
                Schema::table('support_tickets', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE support_tickets SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Pipelines table
        if (Schema::hasTable('pipelines')) {
            if (!Schema::hasColumn('pipelines', 'user_id')) {
                Schema::table('pipelines', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE pipelines SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // Services table
        if (Schema::hasTable('services')) {
            if (!Schema::hasColumn('services', 'user_id')) {
                Schema::table('services', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
                DB::statement('UPDATE services SET user_id = created_by WHERE user_id IS NULL AND created_by IS NOT NULL');
            }
        }

        // ==========================================
        // ADD assigned_user_id COLUMNS
        // ==========================================

        // Leads - assigned_user_id
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'assigned_user_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Deals - assigned_user_id
        if (Schema::hasTable('deals') && !Schema::hasColumn('deals', 'assigned_user_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Tasks - assigned_user_id
        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'assigned_user_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Meetings - assigned_user_id
        if (Schema::hasTable('meetings') && !Schema::hasColumn('meetings', 'assigned_user_id')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Projects - assigned_user_id
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'assigned_user_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Follow-ups - assigned_user_id
        if (Schema::hasTable('follow_ups') && !Schema::hasColumn('follow_ups', 'assigned_user_id')) {
            Schema::table('follow_ups', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Support tickets - assigned_user_id
        if (Schema::hasTable('support_tickets') && !Schema::hasColumn('support_tickets', 'assigned_user_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Pipelines - assigned_user_id
        if (Schema::hasTable('pipelines') && !Schema::hasColumn('pipelines', 'assigned_user_id')) {
            Schema::table('pipelines', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Invoices - assigned_user_id
        if (Schema::hasTable('invoices')) {
            if (!Schema::hasColumn('invoices', 'assigned_user_id')) {
                Schema::table('invoices', function (Blueprint $table) {
                    // Check if user_id exists to place after it
                    if (Schema::hasColumn('invoices', 'user_id')) {
                        $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                    } else {
                        $table->unsignedBigInteger('assigned_user_id')->nullable()->after('id');
                    }
                    $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
                });
            }
        }

        // Bookings - assigned_user_id
        if (Schema::hasTable('bookings') && !Schema::hasColumn('bookings', 'assigned_user_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('user_id');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // ==========================================
        // ADD created_by WHERE MISSING
        // ==========================================

        // Leads - created_by
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'created_by')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Tasks - created_by
        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'created_by')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Projects - created_by
        if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'created_by')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['leads', 'deals', 'tasks', 'meetings', 'projects', 'follow_ups', 'support_tickets', 'pipelines', 'services', 'invoices', 'bookings'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    if (Schema::hasColumn($table, 'user_id')) {
                        $blueprint->dropForeign([$table . '_user_id_foreign']);
                        $blueprint->dropColumn('user_id');
                    }
                    if (Schema::hasColumn($table, 'assigned_user_id')) {
                        $blueprint->dropForeign([$table . '_assigned_user_id_foreign']);
                        $blueprint->dropColumn('assigned_user_id');
                    }
                });
            }
        }
    }
};
