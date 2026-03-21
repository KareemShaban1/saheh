<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_media_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_media_id');
            $table->unsignedBigInteger('patient_id');
            $table->boolean('liked')->default(false);
            $table->boolean('saved')->default(false);
            $table->timestamps();

            $table->unique(['organization_media_id', 'patient_id'], 'media_patient_unique');
            $table->index(['patient_id', 'liked']);
            $table->index(['patient_id', 'saved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_media_interactions');
    }
};

