<?php

namespace App\Models;

use App\Models\Shared\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'question_id',
        'patient_id',
        'organization_id',
        'organization_type',
        'answer_type',
        'answer_text',
        'answer_number',
        'answer_boolean',
        'answer_date',
        'answer_json',
    ];

    protected $casts = [
        'answer_boolean' => 'boolean',
        'answer_number' => 'decimal:2',
        'answer_date' => 'date',
        'answer_json' => 'array',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function question()
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'question_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}

