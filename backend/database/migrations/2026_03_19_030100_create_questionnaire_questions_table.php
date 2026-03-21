<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type', 50);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['questionnaire_id', 'sort_order'], 'questionnaire_questions_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_questions');
    }
};

