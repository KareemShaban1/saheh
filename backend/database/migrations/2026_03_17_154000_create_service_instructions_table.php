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
        Schema::create('service_instructions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')->references('id')->on('services')->onDelete('cascade');

	//   type (pre: before the service, post: after the service)
	$table->enum('type', ['pre','post'])->default('pre');

            $table->text('instructions')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_instructions');
    }
};
