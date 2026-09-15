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
        Schema::table('challenges', function (Blueprint $table) {
            $table->boolean('invite_enabled')->default(true)->after('timezone');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('invite_id')->nullable()->after('challenge_id')->constrained()->nullOnDelete();
        });

        DB::statement('
            update participants p
            join invites i on i.challenge_id = p.challenge_id and i.used_by_user_id = p.user_id
            set p.invite_id = i.id
        ');

        Schema::table('invites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('used_by_user_id');
            $table->dropColumn('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invites', function (Blueprint $table) {
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invite_id');
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('invite_enabled');
        });
    }
};
