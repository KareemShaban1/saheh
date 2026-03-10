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
        Schema::create('lab_service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_service_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_service_id')->constrained()->cascadeOnDelete();
            // medical_analysis_id , MedicalAnalysis::class
            $table->nullableMorphs('module');
            
            $table->string('name');
            $table->string('value')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit');
            $table->string('normal_range');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_service_options');
    }
};
