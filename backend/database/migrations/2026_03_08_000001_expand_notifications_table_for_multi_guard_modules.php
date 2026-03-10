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
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'guard_name')) {
                $table->string('guard_name')->nullable()->after('notifiable_id')->index();
            }
            if (!Schema::hasColumn('notifications', 'module')) {
                $table->string('module')->nullable()->after('guard_name')->index();
            }
            if (!Schema::hasColumn('notifications', 'event')) {
                $table->string('event')->nullable()->after('module')->index();
            }

            if (!Schema::hasColumn('notifications', 'actor_type')) {
                $table->string('actor_type')->nullable()->after('event');
            }
            if (!Schema::hasColumn('notifications', 'actor_id')) {
                $table->unsignedBigInteger('actor_id')->nullable()->after('actor_type');
            }
            if (!Schema::hasColumn('notifications', 'organization_type')) {
                $table->string('organization_type')->nullable()->after('actor_id');
            }
            if (!Schema::hasColumn('notifications', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('organization_type');
            }
            if (!Schema::hasColumn('notifications', 'resource_type')) {
                $table->string('resource_type')->nullable()->after('organization_id')->index();
            }
            if (!Schema::hasColumn('notifications', 'resource_id')) {
                $table->unsignedBigInteger('resource_id')->nullable()->after('resource_type')->index();
            }
            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('resource_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'action_url')) {
                $table->dropColumn('action_url');
            }
            if (Schema::hasColumn('notifications', 'resource_id')) {
                $table->dropColumn('resource_id');
            }
            if (Schema::hasColumn('notifications', 'resource_type')) {
                $table->dropColumn('resource_type');
            }
            if (Schema::hasColumn('notifications', 'organization_id')) {
                $table->dropColumn('organization_id');
            }
            if (Schema::hasColumn('notifications', 'organization_type')) {
                $table->dropColumn('organization_type');
            }
            if (Schema::hasColumn('notifications', 'actor_id')) {
                $table->dropColumn('actor_id');
            }
            if (Schema::hasColumn('notifications', 'actor_type')) {
                $table->dropColumn('actor_type');
            }
            if (Schema::hasColumn('notifications', 'event')) {
                $table->dropColumn('event');
            }
            if (Schema::hasColumn('notifications', 'module')) {
                $table->dropColumn('module');
            }
            if (Schema::hasColumn('notifications', 'guard_name')) {
                $table->dropColumn('guard_name');
            }
        });
    }
};
