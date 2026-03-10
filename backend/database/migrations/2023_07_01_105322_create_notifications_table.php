<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Laravel notification class name (required by DatabaseNotification)
            $table->string('type');

            // Receiver (works with all auth guards via polymorphic relation)
            $table->morphs('notifiable');

            // Optional metadata to support all modules/guards with rich filtering
            $table->string('guard_name')->nullable()->index(); // clinic, medical_laboratory, radiology_center, patient, admin
            $table->string('module')->nullable()->index(); // reservations, financial, chat, inventory, etc.
            $table->string('event')->nullable()->index(); // created, updated, cancelled, approved, etc.
            $table->nullableMorphs('actor'); // initiator of action (patient/admin/staff/etc.)
            $table->nullableMorphs('organization'); // clinic/lab/radiology owner scope
            $table->string('resource_type')->nullable()->index();
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->string('action_url')->nullable();

            // Keep JSON for frontend/API dynamic payload rendering
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_idx');
            $table->index(['module', 'event'], 'notifications_module_event_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
