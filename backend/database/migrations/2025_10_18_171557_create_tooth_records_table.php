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
        Schema::create('tooth_records', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('organization');
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            // tooth_number: 1..32 (universal numbering), or you can use FDI codes
            $table->unsignedTinyInteger('tooth_number');
            $table->enum('status', ['healthy','decayed','filled','missing','root_canal','crown'])->default('healthy');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooth_records');
    }
};