<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('completions', function (Blueprint $table) {
            $table->renameColumn('photo_url', 'photo_path');
        });

        foreach (DB::table('completions')->whereNotNull('photo_path')->get(['id', 'photo_path']) as $row) {
            if (!Str::contains($row->photo_path, '/storage/')) {
                continue;
            }

            DB::table('completions')
            ->where('id', $row->id)
            ->update(['photo_path' => Str::after($row->photo_path, '/storage/')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('completions')->whereNotNull('photo_path')->get(['id', 'photo_path']) as $row) {
            DB::table('completions')
                ->where('id', $row->id)
                ->update(['photo_path' => rtrim(config('app.url'), '/') . '/storage/' . $row->photo_path]);
        }

        Schema::table('completions', function (Blueprint $table) {
            $table->renameColumn('photo_path', 'photo_url');
        });
    }
};
