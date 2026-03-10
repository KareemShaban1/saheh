<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (!Schema::hasColumn('chats', 'peer_user_id')) {
                $table->foreignId('peer_user_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
        DB::statement('ALTER TABLE chats MODIFY patient_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'peer_user_id')) {
                $table->dropConstrainedForeignId('peer_user_id');
            }
        });
        DB::statement('ALTER TABLE chats MODIFY patient_id BIGINT UNSIGNED NOT NULL');
    }
};
