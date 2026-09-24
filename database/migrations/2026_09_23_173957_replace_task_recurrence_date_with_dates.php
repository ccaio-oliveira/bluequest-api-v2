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
            $table->json('recurrence_dates')->nullable()->after('recurrence_type');
            $table->dropColumn('recurrence_date');
        });

        DB::statement("ALTER TABLE tasks MODIFY recurrence_type ENUM('dates', 'daily', 'weekdays') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY recurrence_type ENUM('once', 'daily', 'weekdays' NOT NULL");

        Schema::table('tasks', function (Blueprint $table) {
            $table->date('recurrence_date')->nullable()->after('recurrence_type');
            $table->dropColumn('recurrence_dates');
        });
    }
};
