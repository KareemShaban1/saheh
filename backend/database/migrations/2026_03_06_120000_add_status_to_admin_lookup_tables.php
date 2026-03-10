<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('specialties', 'status')) {
            Schema::table('specialties', function (Blueprint $table) {
                $table->boolean('status')->default(1)->after('description');
            });
        }

        if (!Schema::hasColumn('governorates', 'status')) {
            Schema::table('governorates', function (Blueprint $table) {
                $table->boolean('status')->default(1)->after('name');
            });
        }

        if (!Schema::hasColumn('cities', 'status')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->boolean('status')->default(1)->after('governorate_id');
            });
        }

        if (!Schema::hasColumn('areas', 'status')) {
            Schema::table('areas', function (Blueprint $table) {
                $table->boolean('status')->default(1)->after('governorate_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('specialties', 'status')) {
            Schema::table('specialties', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('governorates', 'status')) {
            Schema::table('governorates', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('cities', 'status')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('areas', 'status')) {
            Schema::table('areas', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
