<?php

use App\Http\Controllers\Api\AccessTokenController;
use App\Http\Controllers\Api\Auth\PatientAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorInformationController;
use App\Http\Controllers\Api\Patient\ChronicDiseaseController;
use App\Http\Controllers\Api\Patient\ClinicController;
use App\Http\Controllers\Api\Patient\DoctorController;
use App\Http\Controllers\Api\Patient\GlassesDistanceController;
use App\Http\Controllers\Api\Patient\HomeController;
use App\Http\Controllers\Api\Patient\MedicalAnalysisController;
use App\Http\Controllers\Api\Patient\MedicalLaboratoryController;
use App\Http\Controllers\Api\Patient\PatientChatApiController;
use App\Http\Controllers\Api\Patient\MessageController;
use App\Http\Controllers\Api\Patient\QuestionnaireController;
use App\Http\Controllers\Api\Patient\PrescriptionController;
use App\Http\Controllers\Api\Patient\RadiologyCenterController;
use App\Http\Controllers\Api\Patient\RayController;
use App\Http\Controllers\FrontApis\clinic\ReservationController;
use App\Http\Controllers\Api\Patient\ReviewsController;
use App\Http\Controllers\Api\Patient\ReelController;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use App\Models\Shared\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\OrganizationMedia;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return Auth::guard('sanctum')->user();
});

Route::post('auth/access-tokens', [AccessTokenController::class, 'store'])
    ->middleware('guest:sanctum');
// guest : don't auth using sanctum

Route::delete('auth/access-tokens/{token?}', [AccessTokenController::class, 'destroy'])
    ->middleware('auth:sanctum');

Route::post('patient/login', [AuthController::class, 'patientLogin'])
    ->middleware('guest:sanctum');

// Public data for organization registration forms (no auth)
Route::get('public/specialties', function () {
    $items = \App\Models\Specialty::orderBy('id')->get(['id', 'name_en', 'name_ar']);
    return response()->json(['data' => $items]);
});
Route::get('public/governorates', function () {
    $items = \App\Models\Governorate::orderBy('id')->get(['id', 'name']);
    return response()->json(['data' => $items]);
});
Route::get('public/cities', function (Request $request) {
    $query = \App\Models\City::query();
    if ($request->filled('governorate_id')) {
        $query->where('governorate_id', $request->governorate_id);
    }
    return response()->json(['data' => $query->orderBy('id')->get(['id', 'name', 'governorate_id'])]);
});
Route::get('public/areas', function (Request $request) {
    $query = \App\Models\Area::query();
    if ($request->filled('city_id')) {
        $query->where('city_id', $request->city_id);
    }
    return response()->json(['data' => $query->orderBy('id')->get(['id', 'name', 'city_id'])]);
});

// Public landing endpoints (no auth)
Route::get('public/landing/overview', function () {
    return response()->json([
        'data' => [
            'counts' => [
                'clinics' => Clinic::count(),
                'doctors' => Doctor::count(),
                'reservations' => Reservation::count(),
                'patients' => Patient::count(),
                'medical_laboratories' => MedicalLaboratory::count(),
                'radiology_centers' => RadiologyCenter::count(),
            ],
        ],
    ]);
});

Route::get('public/landing/clinics', function (Request $request) {
    $perPage = max(1, min((int) $request->get('per_page', 6), 24));
    $clinics = Clinic::query()
        ->select(['id', 'name', 'address', 'specialty_id'])
        ->with(['specialty:id,name_en,name_ar'])
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->orderByDesc('reviews_avg_rating')
        ->orderByDesc('reviews_count')
        ->orderByDesc('id')
        ->take($perPage)
        ->get()
        ->map(function (Clinic $clinic) {
            return [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'address' => $clinic->address,
                'logo' => null,
                'specialty_name' => $clinic->specialty?->name_en ?? $clinic->specialty?->name_ar,
                'rating' => round((float) ($clinic->reviews_avg_rating ?? 0), 1),
                'reviews_count' => (int) ($clinic->reviews_count ?? 0),
            ];
        });

    return response()->json(['data' => $clinics]);
});

Route::get('public/landing/labs', function (Request $request) {
    $perPage = max(1, min((int) $request->get('per_page', 24), 48));
    $labs = MedicalLaboratory::query()
        ->select(['id', 'name', 'address', 'description'])
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->orderByDesc('reviews_avg_rating')
        ->orderByDesc('reviews_count')
        ->orderByDesc('id')
        ->take($perPage)
        ->get()
        ->map(function (MedicalLaboratory $lab) {
            return [
                'id' => $lab->id,
                'name' => $lab->name,
                'address' => $lab->address,
                'logo' => null,
                'description' => $lab->description,
                'rating' => round((float) ($lab->reviews_avg_rating ?? 0), 1),
                'reviews_count' => (int) ($lab->reviews_count ?? 0),
            ];
        });

    return response()->json(['data' => $labs]);
});

Route::get('public/landing/radiology-centers', function (Request $request) {
    $perPage = max(1, min((int) $request->get('per_page', 24), 48));
    $centers = RadiologyCenter::query()
        ->select(['id', 'name', 'address', 'description'])
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->orderByDesc('reviews_avg_rating')
        ->orderByDesc('reviews_count')
        ->orderByDesc('id')
        ->take($perPage)
        ->get()
        ->map(function (RadiologyCenter $center) {
            return [
                'id' => $center->id,
                'name' => $center->name,
                'address' => $center->address,
                'logo' => null,
                'description' => $center->description,
                'rating' => round((float) ($center->reviews_avg_rating ?? 0), 1),
                'reviews_count' => (int) ($center->reviews_count ?? 0),
            ];
        });

    return response()->json(['data' => $centers]);
});

Route::get('public/media', function (Request $request) {
    $validated = validator($request->all(), [
        'owner_type' => 'required|string',
        'owner_id' => 'required|integer|min:1',
        'media_type' => 'nullable|in:reel,video,story',
        'limit' => 'nullable|integer|min:1|max:100',
    ])->validate();

    $ownerTypeMap = [
        'clinic' => Clinic::class,
        'doctor' => Doctor::class,
        'lab' => MedicalLaboratory::class,
        'medical_laboratory' => MedicalLaboratory::class,
        'radiology' => RadiologyCenter::class,
        'radiology_center' => RadiologyCenter::class,
    ];

    $ownerType = $ownerTypeMap[strtolower($validated['owner_type'])] ?? $validated['owner_type'];
    $limit = (int) ($validated['limit'] ?? 30);

    $query = OrganizationMedia::query()
        ->where('owner_type', $ownerType)
        ->where('owner_id', (int) $validated['owner_id'])
        ->where('is_active', true)
        ->where(function ($q) {
            $q->where('media_type', '!=', 'story')
                ->orWhere('created_at', '>=', now()->subDay());
        })
        ->orderByDesc('sort_order')
        ->orderByDesc('id')
        ->limit($limit);

    if (!empty($validated['media_type'])) {
        $query->where('media_type', $validated['media_type']);
    }

    $items = $query->get()->map(function (OrganizationMedia $item) {
        return [
            'id' => $item->id,
            'owner_type' => $item->owner_type,
            'owner_id' => $item->owner_id,
            'media_type' => $item->media_type,
            'title' => $item->title,
            'description' => $item->description,
            'file_url' => url('storage/' . ltrim($item->file_path, '/')),
            'mime_type' => $item->mime_type,
            'duration_seconds' => $item->duration_seconds,
            'created_at' => $item->created_at,
        ];
    });

    return response()->json(['data' => $items]);
});

Route::get('public/reels', function (Request $request) {
    $limit = max(1, min((int) $request->get('limit', 30), 100));

    $items = OrganizationMedia::query()
        ->where('media_type', 'reel')
        ->where('is_active', true)
        ->orderByDesc('sort_order')
        ->orderByDesc('id')
        ->limit($limit)
        ->get()
        ->map(function (OrganizationMedia $item) {
            return [
                'id' => $item->id,
                'owner_type' => $item->owner_type,
                'owner_id' => $item->owner_id,
                'media_type' => $item->media_type,
                'title' => $item->title,
                'description' => $item->description,
                'file_url' => url('storage/' . ltrim($item->file_path, '/')),
                'mime_type' => $item->mime_type,
                'duration_seconds' => $item->duration_seconds,
                'created_at' => $item->created_at,
            ];
        });

    return response()->json(['data' => $items]);
});

Route::prefix('patient')->group(function () {
    Route::post('register', [PatientAuthController::class, 'register']);
    Route::post('login', [PatientAuthController::class, 'login']);
    Route::post('forgot-password', [PatientAuthController::class, 'forgotPassword']);

    Route::middleware('auth:patient_api')->group(function () {

        Route::post('logout', [PatientAuthController::class, 'logout']);
        Route::post('update-profile', [PatientAuthController::class, 'updateProfile']);
        Route::post('change-password', [PatientAuthController::class, 'changePassword']);
        Route::post('delete-profile', [PatientAuthController::class, 'deleteProfile']);
        Route::get('profile', [PatientAuthController::class, 'getProfile']);

        Route::get('reservations', [ReservationController::class, 'index']);
        Route::get('reservation/{id}', [ReservationController::class, 'show']);
        Route::post('store_reservation', [ReservationController::class, 'store']);
        Route::post('change_reservation_status/{id}/{status}', [ReservationController::class, 'changeReservationStatus']);

        Route::get('clinics', [ClinicController::class, 'index']);
        Route::get('clinic/{id}', [ClinicController::class, 'show']);

        Route::get('chronic_diseases', [ChronicDiseaseController::class, 'index']);
        Route::get('chronic_disease/{id}', [ChronicDiseaseController::class, 'show']);


        Route::get('glasses_distances', [GlassesDistanceController::class, 'index']);
        Route::get('glasses_distance/{id}', [GlassesDistanceController::class, 'show']);

        Route::get('prescriptions', [PrescriptionController::class, 'index']);
        Route::get('prescription/{id}', [PrescriptionController::class, 'show']);

        Route::get('medical_analyses', [MedicalAnalysisController::class, 'index']);
        Route::get('medical_analysis/{id}', [MedicalAnalysisController::class, 'show']);

        Route::get('rays', [RayController::class, 'index']);
        Route::get('ray/{id}', [RayController::class, 'show']);


        Route::get('doctors', [DoctorController::class, 'index']);
        Route::get('doctor/{id}', [DoctorController::class, 'show']);
        Route::get('doctor_number_of_reservations', [DoctorController::class, 'doctorNumberOfReservations']);
        Route::get('doctor_reservation_slots', [DoctorController::class, 'doctorSlots']);
        Route::get('doctor_reservation_slots_number', [DoctorController::class, 'doctorReservationSlotsNumbers']);
        Route::get('doctor_services/{doctor_id}', [DoctorController::class, 'getServices']);

        Route::post('send_message', [MessageController::class, 'store']);
        Route::get('chat/contacts', [PatientChatApiController::class, 'contacts']);
        Route::get('chat/conversations', [PatientChatApiController::class, 'conversations']);
        Route::post('chat/open', [PatientChatApiController::class, 'openConversation']);
        Route::get('chat/conversations/{chatId}/messages', [PatientChatApiController::class, 'messages']);
        Route::post('chat/conversations/{chatId}/messages', [PatientChatApiController::class, 'sendMessage']);

        Route::post('/reviews', [ReviewsController::class, 'store']);
        Route::get('/reviews', [ReviewsController::class, 'index']);
        Route::put('/reviews/{id}', [ReviewsController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewsController::class, 'destroy']);


        Route::get('medical_laboratories', [MedicalLaboratoryController::class, 'index']);
        Route::get('medical_laboratory/{id}', [MedicalLaboratoryController::class, 'show']);

        Route::get('radiology_centers', [RadiologyCenterController::class, 'index']);
        Route::get('radiology_center/{id}', [RadiologyCenterController::class, 'show']);

        Route::get('questionnaires', [QuestionnaireController::class, 'index']);
        Route::get('questionnaires/{id}/answers', [QuestionnaireController::class, 'myAnswers']);
        Route::post('questionnaires/{id}/answers', [QuestionnaireController::class, 'submitAnswers']);

        Route::get('home', [HomeController::class, 'index']);
        Route::get('reels', [ReelController::class, 'feed']);
        Route::post('reels/{id}/toggle-like', [ReelController::class, 'toggleLike']);
        Route::post('reels/{id}/toggle-save', [ReelController::class, 'toggleSave']);
    });
});

Broadcast::routes(['middleware' => ['auth:patient_api']]);

include 'frontendApis.php';


