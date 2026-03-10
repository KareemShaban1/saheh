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
        Schema::create('module_services', function (Blueprint $table) {
            $table->id();
            $table->morphs('module');
            $table->foreignId('service_fee_id')->references('id')->on('services')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->decimal('fee', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_services');
    }
};
