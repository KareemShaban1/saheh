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
        Schema::create('prescription_drugs', function (Blueprint $table) {
            $table->id();
			$table->foreignId('prescription_id')->references('id')->on('prescriptions')->onDelete('cascade');
			$table->foreignId('drug_id')->references('id')->on('drugs')->onDelete('cascade');
			$table->string('dose');
			$table->string('type');
			$table->string('frequency');
			$table->string('period');
			$table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_drugs');
    }
};
