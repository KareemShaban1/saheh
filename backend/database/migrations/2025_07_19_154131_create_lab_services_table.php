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
        Schema::create('lab_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lab_service_category_id')->constrained('lab_service_categories')->cascadeOnDelete();
            $table->nullableMorphs('organization');

            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('unit');
            $table->string('normal_range');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_services');
    }
};
