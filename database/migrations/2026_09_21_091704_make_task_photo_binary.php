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
        DB::table('tasks')->where('photo_requirement', 'optional')->update(['photo_requirement' => 'required']);

        DB::statement("ALTER TABLE tasks MODIFY photo_requirement ENUM('none', 'required') NOT NULL DEFAULT 'none'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY photo_requirement ENUM('none', 'optional', 'required') NOT NULL DEFAULT 'none'");
    }
};
