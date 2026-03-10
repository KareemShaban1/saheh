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
        Schema::create('patient_reviews', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('organization');
            $table->foreignId('doctor_id')->nullable()->references('id')->on('doctors')->onDelete('cascade');
            $table->foreignId('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->unsignedTinyInteger('rating')->comment('Rating from 1 to 5');
            $table->text('comment');
            $table->foreignId('changed_by')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // Ensure one review per patient per entity (doctor or organization)
            $table->unique(['patient_id', 'doctor_id'], 'unique_patient_doctor_review');
            $table->unique(['patient_id', 'organization_id', 'organization_type' , 'doctor_id'], 'unique_patient_organization_review');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_reviews');
    }
};
