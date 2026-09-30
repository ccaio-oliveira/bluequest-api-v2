<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('recurrence_times_per_week')->nullable()->after('recurrence_dates');
        });

        DB::statement("ALTER TABLE tasks MODIFY recurrence_type ENUM('dates', 'daily', 'weekdays', 'weekly') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY recurrence_type ENUM('dates', 'daily', 'weekdays') NOT NULL");

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('recurrence_times_per_week');
        });
    }
};
