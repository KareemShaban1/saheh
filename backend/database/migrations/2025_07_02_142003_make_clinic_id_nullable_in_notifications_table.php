<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('notifications', 'clinic_id')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('clinic_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('notifications', 'clinic_id')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('clinic_id')->nullable(false)->change();
        });
    }
};
