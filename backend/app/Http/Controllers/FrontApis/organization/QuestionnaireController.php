<?php

namespace App\Http\Controllers\FrontApis\organization;

use App\Http\Controllers\BaseFrontApiController;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionnaireController extends BaseFrontApiController
{
    private const QUESTION_TYPES = [
        'short_text',
        'long_text',
        'number',
        'boolean',
        'date',
        'single_choice',
        'multiple_choice',
    ];

    public function index(Request $request)
    {
        $authUser = $request->user();
        [$organizationId, $organizationType] = $this->organizationContext($authUser);

        $rows = Questionnaire::query()
            ->where('organization_id', $organizationId)
            ->where('organization_type', $organizationType)
            ->withCount('questions')
            ->latest('id')
            ->get()
            ->map(fn (Questionnaire $q) => [
                'id' => $q->id,
                'title' => $q->title,
                'description' => $q->description,
                'is_active' => (bool) $q->is_active,
                'questions_count' => (int) $q->questions_count,
                'created_at' => optional($q->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => optional($q->updated_at)->format('Y-m-d H:i:s'),
            ])
            ->values();

        return $this->returnJSON(['data' => $rows], 'Questionnaires', 'success');
    }

    public function show(Request $request, $id)
    {
        $authUser = $request->user();
        [$organizationId, $organizationType] = $this->organizationContext($authUser);

        $questionnaire = Questionnaire::query()
            ->with('questions')
            ->where('organization_id', $organizationId)
            ->where('organization_type', $organizationType)
            ->findOrFail($id);

        return $this->returnJSON($this->formatQuestionnaire($questionnaire), 'Questionnaire details', 'success');
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        [$organizationId, $organizationType] = $this->organizationContext($authUser);
        $validated = $this->validateQuestionnairePayload($request);

        $questionnaire = DB::transaction(function () use ($validated, $organizationId, $organizationType) {
            $questionnaire = Questionnaire::query()->create([
                'organization_id' => $organizationId,
                'organization_type' => $organizationType,
                'title' => trim((string) $validated['title']),
                'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            foreach ($validated['questions'] as $index => $question) {
                $this->assertChoiceOptionsShape($question, $index);
                $questionnaire->questions()->create([
                    'question_text' => trim((string) $question['question_text']),
                    'question_type' => (string) $question['question_type'],
                    'is_required' => (bool) ($question['is_required'] ?? false),
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                    'placeholder' => isset($question['placeholder']) ? trim((string) $question['placeholder']) : null,
                    'options' => $question['options'] ?? null,
                    'meta' => $question['meta'] ?? null,
                ]);
            }

            return $questionnaire->load('questions');
        });

        return $this->returnJSON($this->formatQuestionnaire($questionnaire), 'Questionnaire created', 'success');
    }

    public function update(Request $request, $id)
    {
        $authUser = $request->user();
        [$organizationId, $organizationType] = $this->organizationContext($authUser);
        $validated = $this->validateQuestionnairePayload($request);

        $questionnaire = Questionnaire::query()
            ->where('organization_id', $organizationId)
            ->where('organization_type', $organizationType)
            ->findOrFail($id);

        $questionnaire = DB::transaction(function () use ($questionnaire, $validated) {
            $questionnaire->update([
                'title' => trim((string) $validated['title']),
                'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            $questionnaire->questions()->delete();
            foreach ($validated['questions'] as $index => $question) {
                $this->assertChoiceOptionsShape($question, $index);
                $questionnaire->questions()->create([
                    'question_text' => trim((string) $question['question_text']),
                    'question_type' => (string) $question['question_type'],
                    'is_required' => (bool) ($question['is_required'] ?? false),
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                    'placeholder' => isset($question['placeholder']) ? trim((string) $question['placeholder']) : null,
                    'options' => $question['options'] ?? null,
                    'meta' => $question['meta'] ?? null,
                ]);
            }

            return $questionnaire->load('questions');
        });

        return $this->returnJSON($this->formatQuestionnaire($questionnaire), 'Questionnaire updated', 'success');
    }

    public function destroy(Request $request, $id)
    {
        $authUser = $request->user();
        [$organizationId, $organizationType] = $this->organizationContext($authUser);

        $questionnaire = Questionnaire::query()
            ->where('organization_id', $organizationId)
            ->where('organization_type', $organizationType)
            ->findOrFail($id);

        $questionnaire->delete();

        return $this->returnJSON(['id' => (int) $id], 'Questionnaire deleted', 'success');
    }

    public function answers(Request $request, $id)
    {
        $authUser = $request->user();
        [$organizationId, $organizationType] = $this->organizationContext($authUser);

        $questionnaire = Questionnaire::query()
            ->with(['answers.question:id,questionnaire_id,question_text,question_type', 'answers.patient:id,name,phone'])
            ->where('organization_id', $organizationId)
            ->where('organization_type', $organizationType)
            ->findOrFail($id);

        $rows = $questionnaire->answers
            ->groupBy('patient_id')
            ->map(function ($patientAnswers) {
                $first = $patientAnswers->first();
                return [
                    'patient' => [
                        'id' => $first?->patient?->id,
                        'name' => $first?->patient?->name,
                        'phone' => $first?->patient?->phone,
                    ],
                    'answers' => $patientAnswers->map(fn ($answer) => [
                        'question_id' => $answer->question_id,
                        'question_text' => $answer->question?->question_text,
                        'question_type' => $answer->question?->question_type,
                        'answer_type' => $answer->answer_type,
                        'answer_text' => $answer->answer_text,
                        'answer_number' => $answer->answer_number,
                        'answer_boolean' => $answer->answer_boolean,
                        'answer_date' => optional($answer->answer_date)->format('Y-m-d'),
                        'answer_json' => $answer->answer_json,
                        'updated_at' => optional($answer->updated_at)->format('Y-m-d H:i:s'),
                    ])->values(),
                ];
            })
            ->values();

        return $this->returnJSON([
            'questionnaire_id' => $questionnaire->id,
            'answers' => $rows,
        ], 'Questionnaire answers', 'success');
    }

    private function validateQuestionnairePayload(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:5000',
            'questions.*.question_type' => ['required', 'string', Rule::in(self::QUESTION_TYPES)],
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.sort_order' => 'nullable|integer|min:0',
            'questions.*.placeholder' => 'nullable|string|max:255',
            'questions.*.options' => 'nullable|array',
            'questions.*.meta' => 'nullable|array',
        ]);
    }

    private function assertChoiceOptionsShape(array $question, int $index): void
    {
        $type = (string) ($question['question_type'] ?? '');
        $isChoice = in_array($type, ['single_choice', 'multiple_choice'], true);
        if (!$isChoice) {
            return;
        }

        $options = $question['options'] ?? null;
        if (!is_array($options) || empty($options)) {
            throw ValidationException::withMessages([
                "questions.$index.options" => ['Choice questions must include options.'],
            ]);
        }

        foreach ($options as $optionIndex => $option) {
            if (!is_string($option) || trim($option) === '') {
                throw ValidationException::withMessages([
                    "questions.$index.options.$optionIndex" => ['Each option must be a non-empty string.'],
                ]);
            }
        }
    }

    private function formatQuestionnaire(Questionnaire $questionnaire): array
    {
        return [
            'id' => $questionnaire->id,
            'title' => $questionnaire->title,
            'description' => $questionnaire->description,
            'is_active' => (bool) $questionnaire->is_active,
            'organization_id' => (int) $questionnaire->organization_id,
            'organization_type' => (string) $questionnaire->organization_type,
            'questions' => $questionnaire->questions->map(fn ($q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_type' => $q->question_type,
                'is_required' => (bool) $q->is_required,
                'sort_order' => (int) $q->sort_order,
                'placeholder' => $q->placeholder,
                'options' => $q->options ?? [],
                'meta' => $q->meta ?? new \stdClass(),
            ])->values(),
            'created_at' => optional($questionnaire->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($questionnaire->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function organizationContext($authUser): array
    {
        $organizationId = (int) ($authUser->organization_id ?? 0);
        $organizationType = (string) ($authUser->organization_type ?? '');

        if ($organizationId <= 0 || $organizationType === '') {
            abort(403, 'Organization context is invalid.');
        }

        return [$organizationId, $organizationType];
    }
}

