<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\PatientOrganization;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAnswer;
use App\Models\RadiologyCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionnaireController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $patient = Auth::guard('patient_api')->user();
            [$organizationType, $organizationId] = $this->resolveOrganizationFilter($request);

            if (!$this->isAssignedToOrganization((int) $patient->id, $organizationType, $organizationId)) {
                return $this->apiResponse(null, 'You are not assigned to this organization', 403, false);
            }

            $rows = Questionnaire::query()
                ->with('questions')
                ->where('organization_type', $organizationType)
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->get()
                ->map(fn (Questionnaire $q) => [
                    'id' => $q->id,
                    'title' => $q->title,
                    'description' => $q->description,
                    'questions' => $q->questions->map(fn ($question) => [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'is_required' => (bool) $question->is_required,
                        'sort_order' => (int) $question->sort_order,
                        'placeholder' => $question->placeholder,
                        'options' => $question->options ?? [],
                        'meta' => $question->meta ?? new \stdClass(),
                    ])->values(),
                ])
                ->values();

            return $this->apiResponse($rows, 'Questionnaires fetched successfully', 200);
        } catch (\Throwable $e) {
            return $this->apiResponse(null, $e->getMessage(), 500, false);
        }
    }

    public function submitAnswers(Request $request, $id)
    {
        try {
            $patient = Auth::guard('patient_api')->user();
            $validated = $request->validate([
                'answers' => 'required|array|min:1',
                'answers.*.question_id' => 'required|integer',
                'answers.*.answer_text' => 'nullable|string',
                'answers.*.answer_number' => 'nullable|numeric',
                'answers.*.answer_boolean' => 'nullable|boolean',
                'answers.*.answer_date' => 'nullable|date',
                'answers.*.answer_json' => 'nullable|array',
            ]);

            $questionnaire = Questionnaire::query()
                ->with('questions')
                ->findOrFail($id);

            if (!$questionnaire->is_active) {
                return $this->apiResponse(null, 'Questionnaire is not active', 422, false);
            }

            if (!$this->isAssignedToOrganization((int) $patient->id, (string) $questionnaire->organization_type, (int) $questionnaire->organization_id)) {
                return $this->apiResponse(null, 'You are not assigned to this organization', 403, false);
            }

            $questionMap = $questionnaire->questions->keyBy('id');
            $answersByQuestion = collect($validated['answers'])->keyBy(fn ($row) => (int) $row['question_id']);

            foreach ($questionnaire->questions as $question) {
                if (!(bool) $question->is_required) {
                    continue;
                }

                $answerRow = $answersByQuestion->get((int) $question->id);
                if (!$answerRow || !$this->hasMeaningfulAnswer($answerRow, (string) $question->question_type)) {
                    throw ValidationException::withMessages([
                        "answers.question_{$question->id}" => ['This required question must be answered.'],
                    ]);
                }
            }

            DB::transaction(function () use ($validated, $patient, $questionnaire, $questionMap) {
                foreach ($validated['answers'] as $row) {
                    $questionId = (int) $row['question_id'];
                    $question = $questionMap->get($questionId);
                    if (!$question) {
                        throw ValidationException::withMessages([
                            'answers' => ["Question {$questionId} does not belong to this questionnaire."],
                        ]);
                    }

                    $normalized = $this->normalizeAnswerByType($row, (string) $question->question_type);

                    QuestionnaireAnswer::query()->updateOrCreate(
                        [
                            'questionnaire_id' => (int) $questionnaire->id,
                            'question_id' => $questionId,
                            'patient_id' => (int) $patient->id,
                        ],
                        [
                            'organization_id' => (int) $questionnaire->organization_id,
                            'organization_type' => (string) $questionnaire->organization_type,
                            'answer_type' => (string) $question->question_type,
                            'answer_text' => $normalized['answer_text'],
                            'answer_number' => $normalized['answer_number'],
                            'answer_boolean' => $normalized['answer_boolean'],
                            'answer_date' => $normalized['answer_date'],
                            'answer_json' => $normalized['answer_json'],
                        ],
                    );
                }
            });

            return $this->apiResponse(['questionnaire_id' => (int) $questionnaire->id], 'Answers submitted successfully', 200);
        } catch (ValidationException $e) {
            return $this->apiResponse($e->errors(), 'Validation failed', 422, false);
        } catch (\Throwable $e) {
            return $this->apiResponse(null, $e->getMessage(), 500, false);
        }
    }

    public function myAnswers($id)
    {
        try {
            $patient = Auth::guard('patient_api')->user();

            $questionnaire = Questionnaire::query()
                ->with('questions')
                ->findOrFail($id);

            if (!$this->isAssignedToOrganization((int) $patient->id, (string) $questionnaire->organization_type, (int) $questionnaire->organization_id)) {
                return $this->apiResponse(null, 'You are not assigned to this organization', 403, false);
            }

            $answers = QuestionnaireAnswer::query()
                ->where('questionnaire_id', (int) $questionnaire->id)
                ->where('patient_id', (int) $patient->id)
                ->get()
                ->keyBy('question_id');

            $payload = [
                'questionnaire_id' => (int) $questionnaire->id,
                'title' => $questionnaire->title,
                'description' => $questionnaire->description,
                'answers' => $questionnaire->questions->map(function ($question) use ($answers) {
                    $answer = $answers->get((int) $question->id);
                    return [
                        'question_id' => (int) $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'is_required' => (bool) $question->is_required,
                        'answer_text' => $answer?->answer_text,
                        'answer_number' => $answer?->answer_number,
                        'answer_boolean' => $answer?->answer_boolean,
                        'answer_date' => optional($answer?->answer_date)->format('Y-m-d'),
                        'answer_json' => $answer?->answer_json,
                    ];
                })->values(),
            ];

            return $this->apiResponse($payload, 'Answers fetched successfully', 200);
        } catch (\Throwable $e) {
            return $this->apiResponse(null, $e->getMessage(), 500, false);
        }
    }

    private function resolveOrganizationFilter(Request $request): array
    {
        $request->validate([
            'organization_type' => 'required|string',
            'organization_id' => 'required|integer|min:1',
        ]);

        $organizationType = $this->normalizeOrganizationType((string) $request->input('organization_type'));
        $organizationId = (int) $request->input('organization_id');

        if (!$organizationType) {
            throw ValidationException::withMessages([
                'organization_type' => ['Organization type is invalid.'],
            ]);
        }

        return [$organizationType, $organizationId];
    }

    private function normalizeOrganizationType(string $organizationType): ?string
    {
        $normalized = strtolower(trim($organizationType));
        return match ($normalized) {
            'clinic', 'app\\models\\clinic' => Clinic::class,
            'medical_laboratory', 'medical-laboratory', 'lab', 'app\\models\\medicallaboratory' => MedicalLaboratory::class,
            'radiology_center', 'radiology-center', 'radiology', 'app\\models\\radiologycenter' => RadiologyCenter::class,
            default => null,
        };
    }

    private function isAssignedToOrganization(int $patientId, string $organizationType, int $organizationId): bool
    {
        return PatientOrganization::query()
            ->where('patient_id', $patientId)
            ->where('organization_type', $organizationType)
            ->where('organization_id', $organizationId)
            ->where('assigned', true)
            ->exists();
    }

    private function hasMeaningfulAnswer(array $row, string $questionType): bool
    {
        $normalized = $this->normalizeAnswerByType($row, $questionType);
        return $normalized['answer_text'] !== null
            || $normalized['answer_number'] !== null
            || $normalized['answer_boolean'] !== null
            || $normalized['answer_date'] !== null
            || $normalized['answer_json'] !== null;
    }

    private function normalizeAnswerByType(array $row, string $questionType): array
    {
        $type = strtolower(trim($questionType));
        $base = [
            'answer_text' => null,
            'answer_number' => null,
            'answer_boolean' => null,
            'answer_date' => null,
            'answer_json' => null,
        ];

        return match ($type) {
            'short_text', 'long_text', 'single_choice' => [
                ...$base,
                'answer_text' => isset($row['answer_text']) && trim((string) $row['answer_text']) !== ''
                    ? trim((string) $row['answer_text'])
                    : null,
            ],
            'number' => [
                ...$base,
                'answer_number' => isset($row['answer_number']) ? (float) $row['answer_number'] : null,
            ],
            'boolean' => [
                ...$base,
                'answer_boolean' => array_key_exists('answer_boolean', $row) ? (bool) $row['answer_boolean'] : null,
            ],
            'date' => [
                ...$base,
                'answer_date' => isset($row['answer_date']) ? (string) $row['answer_date'] : null,
            ],
            'multiple_choice' => [
                ...$base,
                'answer_json' => isset($row['answer_json']) && is_array($row['answer_json']) ? array_values($row['answer_json']) : null,
            ],
            default => $base,
        };
    }
}

