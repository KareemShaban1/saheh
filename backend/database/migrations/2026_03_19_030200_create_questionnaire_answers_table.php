<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questionnaire_questions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id');
            $table->string('organization_type');
            $table->string('answer_type', 50);
            $table->text('answer_text')->nullable();
            $table->decimal('answer_number', 12, 2)->nullable();
            $table->boolean('answer_boolean')->nullable();
            $table->date('answer_date')->nullable();
            $table->json('answer_json')->nullable();
            $table->timestamps();

            $table->unique(['questionnaire_id', 'question_id', 'patient_id'], 'questionnaire_patient_unique_answer');
            $table->index(['organization_type', 'organization_id'], 'questionnaire_answers_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_answers');
    }
};

