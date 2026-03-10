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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
               // Make the user morphable to any 'organization' model
               $table->nullableMorphs('organization');
                // (clinics , medical_laboratories , radiology_centers)
               // Creates organization_id (unsignedBigInteger) and organization_type (string)

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('job_title');
            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'organization_type','email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
