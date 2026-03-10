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
use App\Http\Controllers\Api\Patient\PrescriptionController;
use App\Http\Controllers\Api\Patient\RadiologyCenterController;
use App\Http\Controllers\Api\Patient\RayController;
use App\Http\Controllers\Api\Patient\ReservationController;
use App\Http\Controllers\Api\Patient\ReviewsController;
use App\Http\Controllers\FrontApis\AdminAuthApiController;
use App\Http\Controllers\FrontApis\AdminDashboardApiController;
use App\Http\Controllers\FrontApis\clinic\ClinicDashboardApiController;
use App\Http\Controllers\FrontApis\medicalLaboratory\MedicalLaboratoryDashboardApiController;
use App\Http\Controllers\FrontApis\OrganizationChatApiController;
use App\Http\Controllers\FrontApis\OrganizationAuthApiController;
use App\Http\Controllers\FrontApis\radiologyCenter\RadiologyCenterDashboardApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

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

        Route::get('home', [HomeController::class, 'index']);
    });
});

Broadcast::routes(['middleware' => ['auth:patient_api']]);

// Organization (Clinic / Lab / Radiology) login & register – no auth
Route::post('clinic/login', [OrganizationAuthApiController::class, 'clinicLogin']);
Route::post('clinic/register', [OrganizationAuthApiController::class, 'clinicRegister']);
Route::post('medicalLaboratory/login', [OrganizationAuthApiController::class, 'medicalLaboratoryLogin']);
Route::post('medicalLaboratory/register', [OrganizationAuthApiController::class, 'medicalLaboratoryRegister']);
Route::post('radiologyCenter/login', [OrganizationAuthApiController::class, 'radiologyCenterLogin']);
Route::post('radiologyCenter/register', [OrganizationAuthApiController::class, 'radiologyCenterRegister']);
Route::post('organization/logout', [OrganizationAuthApiController::class, 'logout'])->middleware('auth:organization_api');
Route::middleware('auth:organization_api')->prefix('organization')->group(function () {
    Route::get('/profile', [OrganizationAuthApiController::class, 'profile']);
    Route::put('/profile', [OrganizationAuthApiController::class, 'updateProfile']);
});
Route::prefix('organization/chat')->middleware('auth:organization_api')->group(function () {
    Route::get('/contacts', [OrganizationChatApiController::class, 'contacts']);
    Route::get('/conversations', [OrganizationChatApiController::class, 'conversations']);
    Route::post('/open', [OrganizationChatApiController::class, 'openConversation']);
    Route::get('/conversations/{chatId}/messages', [OrganizationChatApiController::class, 'messages']);
    Route::post('/conversations/{chatId}/messages', [OrganizationChatApiController::class, 'sendMessage']);
});

// Admin login/logout/profile for React frontend (Bearer token via Sanctum)
Route::post('admin/login', [AdminAuthApiController::class, 'login']);
Route::middleware('auth:admin_api')->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthApiController::class, 'logout']);
    Route::get('/profile', [AdminAuthApiController::class, 'profile']);
    Route::put('/profile', [AdminAuthApiController::class, 'updateProfile']);
});

// Front APIs: Dashboard pages (JSON for React frontend) – token or session
Route::prefix('clinic')->middleware(['auth:organization_api', 'organization.type:clinic', 'organization.api.permission'])->group(function () {
    Route::get('/dashboard', [ClinicDashboardApiController::class, 'dashboard']);
    Route::get('/financial', [ClinicDashboardApiController::class, 'financial']);
    Route::get('/reservations/data', [ClinicDashboardApiController::class, 'reservationsData']);
    Route::get('/reservations/options', [ClinicDashboardApiController::class, 'reservationOptions']);
    Route::get('/reservations/{id}', [ClinicDashboardApiController::class, 'reservationDetails']);
    Route::post('/reservations', [ClinicDashboardApiController::class, 'createReservation']);
    Route::put('/reservations/{id}', [ClinicDashboardApiController::class, 'updateReservation']);
    Route::get('/reservations/{id}/prescription', [ClinicDashboardApiController::class, 'reservationPrescription']);
    Route::post('/reservations/{id}/prescription', [ClinicDashboardApiController::class, 'saveReservationPrescription']);
    Route::get('/reservations/{id}/glasses-distances', [ClinicDashboardApiController::class, 'reservationGlassesDistances']);
    Route::post('/reservations/{id}/glasses-distances', [ClinicDashboardApiController::class, 'createReservationGlassesDistance']);
    Route::get('/reservations/{id}/teeth', [ClinicDashboardApiController::class, 'reservationTeeth']);
    Route::post('/reservations/{id}/teeth', [ClinicDashboardApiController::class, 'saveReservationTeeth']);
    Route::get('/reservations/{id}/rays', [ClinicDashboardApiController::class, 'reservationRays']);
    Route::post('/reservations/{id}/rays', [ClinicDashboardApiController::class, 'createReservationRay']);
    Route::get('/doctors', [ClinicDashboardApiController::class, 'doctors']);
    Route::get('/doctors/{id}', [ClinicDashboardApiController::class, 'doctorDetails']);
    Route::post('/doctors', [ClinicDashboardApiController::class, 'createDoctor']);
    Route::put('/doctors/{id}', [ClinicDashboardApiController::class, 'updateDoctor']);
    Route::get('/doctors/{doctorId}/service-fees', [ClinicDashboardApiController::class, 'doctorServices']);
    Route::get('/specialties', [ClinicDashboardApiController::class, 'specialties']);
    Route::get('/patients', [ClinicDashboardApiController::class, 'patients']);
    Route::get('/patients/{id}', [ClinicDashboardApiController::class, 'patientDetails']);
    Route::get('/patients/{id}/history', [ClinicDashboardApiController::class, 'patientHistory']);
    Route::post('/patients', [ClinicDashboardApiController::class, 'createPatient']);
    Route::post('/patients/assign', [ClinicDashboardApiController::class, 'assignPatientByCode']);
    Route::put('/patients/{id}', [ClinicDashboardApiController::class, 'updatePatient']);
    Route::get('/patients/{id}/glasses-distances', [ClinicDashboardApiController::class, 'patientGlassesDistances']);
    Route::post('/patients/{id}/glasses-distances', [ClinicDashboardApiController::class, 'createPatientGlassesDistance']);
    Route::get('/roles', [ClinicDashboardApiController::class, 'roles']);
    Route::get('/roles/{id}', [ClinicDashboardApiController::class, 'roleDetails']);
    Route::post('/roles', [ClinicDashboardApiController::class, 'createRole']);
    Route::put('/roles/{id}', [ClinicDashboardApiController::class, 'updateRole']);
    Route::get('/permissions', [ClinicDashboardApiController::class, 'permissions']);
    Route::get('/reservation-numbers', [ClinicDashboardApiController::class, 'reservationNumbers']);
    Route::get('/reservation-slots', [ClinicDashboardApiController::class, 'reservationSlots']);
    Route::get('/reviews', [ClinicDashboardApiController::class, 'reviews']);
    Route::get('/announcements', [ClinicDashboardApiController::class, 'announcements']);
    Route::get('/notifications', [ClinicDashboardApiController::class, 'notifications']);
    Route::get('/users', [ClinicDashboardApiController::class, 'users']);
    Route::get('/users/{id}', [ClinicDashboardApiController::class, 'userDetails']);
    Route::post('/users', [ClinicDashboardApiController::class, 'createUser']);
    Route::put('/users/{id}', [ClinicDashboardApiController::class, 'updateUser']);
    Route::delete('/users/{id}', [ClinicDashboardApiController::class, 'deactivateUser']);
    Route::put('/users/{id}/restore', [ClinicDashboardApiController::class, 'restoreUser']);
    Route::get('/services', [ClinicDashboardApiController::class, 'services']);
    Route::get('/services/{id}', [ClinicDashboardApiController::class, 'serviceDetails']);
    Route::post('/services', [ClinicDashboardApiController::class, 'createService']);
    Route::put('/services/{id}', [ClinicDashboardApiController::class, 'updateService']);
    Route::get('/modules', [ClinicDashboardApiController::class, 'modules']);
    Route::get('/inventory/categories', [ClinicDashboardApiController::class, 'inventoryCategories']);
    Route::get('/inventory/movements', [ClinicDashboardApiController::class, 'inventoryMovements']);
    Route::get('/chats', [ClinicDashboardApiController::class, 'chats']);
});

Route::prefix('admin')->middleware(['auth:admin_api'])->group(function () {
    Route::get('/dashboard', [AdminDashboardApiController::class, 'dashboard']);
    Route::get('/financial', [AdminDashboardApiController::class, 'financial']);
    Route::get('/clinics', [AdminDashboardApiController::class, 'clinics']);
    Route::get('/clinics/{id}', [AdminDashboardApiController::class, 'clinicDetails']);
    Route::post('/clinics', [AdminDashboardApiController::class, 'createClinic']);
    Route::put('/clinics/{id}/status', [AdminDashboardApiController::class, 'updateClinicStatus']);
    Route::delete('/clinics/{id}', [AdminDashboardApiController::class, 'deleteClinic']);
    Route::get('/medical-laboratories', [AdminDashboardApiController::class, 'medicalLabs']);
    Route::get('/medical-laboratories/{id}', [AdminDashboardApiController::class, 'medicalLabDetails']);
    Route::post('/medical-laboratories', [AdminDashboardApiController::class, 'createMedicalLab']);
    Route::put('/medical-laboratories/{id}/status', [AdminDashboardApiController::class, 'updateMedicalLabStatus']);
    Route::delete('/medical-laboratories/{id}', [AdminDashboardApiController::class, 'deleteMedicalLab']);
    Route::get('/radiology-centers', [AdminDashboardApiController::class, 'radiologyCenters']);
    Route::get('/radiology-centers/{id}', [AdminDashboardApiController::class, 'radiologyCenterDetails']);
    Route::post('/radiology-centers', [AdminDashboardApiController::class, 'createRadiologyCenter']);
    Route::put('/radiology-centers/{id}/status', [AdminDashboardApiController::class, 'updateRadiologyCenterStatus']);
    Route::delete('/radiology-centers/{id}', [AdminDashboardApiController::class, 'deleteRadiologyCenter']);
    Route::get('/specialties', [AdminDashboardApiController::class, 'specialties']);
    Route::get('/specialties/{id}', [AdminDashboardApiController::class, 'specialtyDetails']);
    Route::post('/specialties', [AdminDashboardApiController::class, 'createSpecialty']);
    Route::put('/specialties/{id}', [AdminDashboardApiController::class, 'updateSpecialty']);
    Route::put('/specialties/{id}/status', [AdminDashboardApiController::class, 'updateSpecialtyStatus']);
    Route::delete('/specialties/{id}', [AdminDashboardApiController::class, 'deleteSpecialty']);
    Route::get('/governorates', [AdminDashboardApiController::class, 'governorates']);
    Route::get('/governorates/{id}', [AdminDashboardApiController::class, 'governorateDetails']);
    Route::post('/governorates', [AdminDashboardApiController::class, 'createGovernorate']);
    Route::put('/governorates/{id}', [AdminDashboardApiController::class, 'updateGovernorate']);
    Route::put('/governorates/{id}/status', [AdminDashboardApiController::class, 'updateGovernorateStatus']);
    Route::delete('/governorates/{id}', [AdminDashboardApiController::class, 'deleteGovernorate']);
    Route::get('/cities', [AdminDashboardApiController::class, 'cities']);
    Route::get('/cities/{id}', [AdminDashboardApiController::class, 'cityDetails']);
    Route::post('/cities', [AdminDashboardApiController::class, 'createCity']);
    Route::put('/cities/{id}', [AdminDashboardApiController::class, 'updateCity']);
    Route::put('/cities/{id}/status', [AdminDashboardApiController::class, 'updateCityStatus']);
    Route::delete('/cities/{id}', [AdminDashboardApiController::class, 'deleteCity']);
    Route::get('/areas', [AdminDashboardApiController::class, 'areas']);
    Route::get('/areas/{id}', [AdminDashboardApiController::class, 'areaDetails']);
    Route::post('/areas', [AdminDashboardApiController::class, 'createArea']);
    Route::put('/areas/{id}', [AdminDashboardApiController::class, 'updateArea']);
    Route::put('/areas/{id}/status', [AdminDashboardApiController::class, 'updateAreaStatus']);
    Route::delete('/areas/{id}', [AdminDashboardApiController::class, 'deleteArea']);
    Route::get('/users', [AdminDashboardApiController::class, 'users']);
    Route::get('/roles', [AdminDashboardApiController::class, 'roles']);
    Route::get('/reviews', [AdminDashboardApiController::class, 'reviews']);
    Route::get('/reviews/{id}', [AdminDashboardApiController::class, 'reviewDetails']);
    Route::post('/reviews', [AdminDashboardApiController::class, 'createReview']);
    Route::put('/reviews/{id}', [AdminDashboardApiController::class, 'updateReview']);
    Route::put('/reviews/{id}/status', [AdminDashboardApiController::class, 'updateReviewStatus']);
    Route::delete('/reviews/{id}', [AdminDashboardApiController::class, 'deleteReview']);
    Route::get('/announcements', [AdminDashboardApiController::class, 'announcements']);
    Route::get('/announcements/{id}', [AdminDashboardApiController::class, 'announcementDetails']);
    Route::post('/announcements', [AdminDashboardApiController::class, 'createAnnouncement']);
    Route::put('/announcements/{id}', [AdminDashboardApiController::class, 'updateAnnouncement']);
    Route::put('/announcements/{id}/status', [AdminDashboardApiController::class, 'updateAnnouncementStatus']);
    Route::delete('/announcements/{id}', [AdminDashboardApiController::class, 'deleteAnnouncement']);
});

Route::prefix('medicalLaboratory')->middleware(['auth:organization_api', 'organization.type:medical_laboratory', 'organization.api.permission'])->group(function () {
    Route::get('/dashboard', [MedicalLaboratoryDashboardApiController::class, 'dashboard']);
    Route::get('/financial', [MedicalLaboratoryDashboardApiController::class, 'financial']);
    Route::get('/notifications', [MedicalLaboratoryDashboardApiController::class, 'notifications']);
    Route::get('/reservations/data', [MedicalLaboratoryDashboardApiController::class, 'reservationsData']);
    Route::get('/patients', [MedicalLaboratoryDashboardApiController::class, 'patients']);
    Route::get('/patients/{id}/history', [MedicalLaboratoryDashboardApiController::class, 'patientHistory']);
    Route::post('/patients', [MedicalLaboratoryDashboardApiController::class, 'createPatient']);
    Route::post('/patients/assign', [MedicalLaboratoryDashboardApiController::class, 'assignPatientByCode']);
    Route::post('/patients/{id}/unassign', [MedicalLaboratoryDashboardApiController::class, 'unassignPatient']);
    Route::get('/users', [MedicalLaboratoryDashboardApiController::class, 'users']);
    Route::get('/users/{id}', [MedicalLaboratoryDashboardApiController::class, 'userDetails']);
    Route::post('/users', [MedicalLaboratoryDashboardApiController::class, 'createUser']);
    Route::put('/users/{id}', [MedicalLaboratoryDashboardApiController::class, 'updateUser']);
    Route::delete('/users/{id}', [MedicalLaboratoryDashboardApiController::class, 'deactivateUser']);
    Route::put('/users/{id}/restore', [MedicalLaboratoryDashboardApiController::class, 'restoreUser']);
    Route::get('/roles', [MedicalLaboratoryDashboardApiController::class, 'roles']);
    Route::get('/roles/{id}', [MedicalLaboratoryDashboardApiController::class, 'roleDetails']);
    Route::post('/roles', [MedicalLaboratoryDashboardApiController::class, 'createRole']);
    Route::put('/roles/{id}', [MedicalLaboratoryDashboardApiController::class, 'updateRole']);
    Route::delete('/roles/{id}', [MedicalLaboratoryDashboardApiController::class, 'deleteRole']);
    Route::get('/permissions', [MedicalLaboratoryDashboardApiController::class, 'permissions']);
    Route::get('/service-categories', [MedicalLaboratoryDashboardApiController::class, 'serviceCategories']);
    Route::get('/service-categories/{id}', [MedicalLaboratoryDashboardApiController::class, 'serviceCategoryDetails']);
    Route::post('/service-categories', [MedicalLaboratoryDashboardApiController::class, 'createServiceCategory']);
    Route::put('/service-categories/{id}', [MedicalLaboratoryDashboardApiController::class, 'updateServiceCategory']);
    Route::delete('/service-categories/{id}', [MedicalLaboratoryDashboardApiController::class, 'deleteServiceCategory']);
    Route::get('/services', [MedicalLaboratoryDashboardApiController::class, 'services']);
    Route::get('/services/{id}', [MedicalLaboratoryDashboardApiController::class, 'serviceDetails']);
    Route::post('/services', [MedicalLaboratoryDashboardApiController::class, 'createService']);
    Route::put('/services/{id}', [MedicalLaboratoryDashboardApiController::class, 'updateService']);
    Route::delete('/services/{id}', [MedicalLaboratoryDashboardApiController::class, 'deleteService']);
    Route::get('/medical-analyses', [MedicalLaboratoryDashboardApiController::class, 'medicalAnalyses']);
    Route::get('/medical-analyses/{id}', [MedicalLaboratoryDashboardApiController::class, 'medicalAnalysisDetails']);
    Route::post('/medical-analyses', [MedicalLaboratoryDashboardApiController::class, 'createMedicalAnalysis']);
    Route::put('/medical-analyses/{id}', [MedicalLaboratoryDashboardApiController::class, 'updateMedicalAnalysis']);
    Route::delete('/medical-analyses/{id}', [MedicalLaboratoryDashboardApiController::class, 'deleteMedicalAnalysis']);
});

Route::prefix('radiologyCenter')->middleware(['auth:organization_api', 'organization.type:radiology_center', 'organization.api.permission'])->group(function () {
    Route::get('/dashboard', [RadiologyCenterDashboardApiController::class, 'dashboard']);
    Route::get('/financial', [RadiologyCenterDashboardApiController::class, 'financial']);
    Route::get('/notifications', [RadiologyCenterDashboardApiController::class, 'notifications']);
    Route::get('/rays', [RadiologyCenterDashboardApiController::class, 'rays']);
    Route::get('/rays/{id}', [RadiologyCenterDashboardApiController::class, 'rayDetails']);
    Route::post('/rays', [RadiologyCenterDashboardApiController::class, 'createRay']);
    Route::put('/rays/{id}', [RadiologyCenterDashboardApiController::class, 'updateRay']);
    Route::delete('/rays/{id}', [RadiologyCenterDashboardApiController::class, 'deleteRay']);
    Route::get('/users', [RadiologyCenterDashboardApiController::class, 'users']);
    Route::get('/users/{id}', [RadiologyCenterDashboardApiController::class, 'userDetails']);
    Route::post('/users', [RadiologyCenterDashboardApiController::class, 'createUser']);
    Route::put('/users/{id}', [RadiologyCenterDashboardApiController::class, 'updateUser']);
    Route::delete('/users/{id}', [RadiologyCenterDashboardApiController::class, 'deleteUser']);
    Route::get('/patients', [RadiologyCenterDashboardApiController::class, 'patients']);
    Route::get('/patients/{id}', [RadiologyCenterDashboardApiController::class, 'patientDetails']);
    Route::get('/patients/{id}/history', [RadiologyCenterDashboardApiController::class, 'patientHistory']);
    Route::post('/patients', [RadiologyCenterDashboardApiController::class, 'createPatient']);
    Route::put('/patients/{id}', [RadiologyCenterDashboardApiController::class, 'updatePatient']);
    Route::delete('/patients/{id}', [RadiologyCenterDashboardApiController::class, 'deletePatient']);
    Route::post('/patients/assign', [RadiologyCenterDashboardApiController::class, 'assignPatientByCode']);
    Route::post('/patients/{id}/unassign', [RadiologyCenterDashboardApiController::class, 'unassignPatient']);
    Route::get('/roles', [RadiologyCenterDashboardApiController::class, 'roles']);
    Route::get('/roles/{id}', [RadiologyCenterDashboardApiController::class, 'roleDetails']);
    Route::post('/roles', [RadiologyCenterDashboardApiController::class, 'createRole']);
    Route::put('/roles/{id}', [RadiologyCenterDashboardApiController::class, 'updateRole']);
    Route::delete('/roles/{id}', [RadiologyCenterDashboardApiController::class, 'deleteRole']);
    Route::get('/permissions', [RadiologyCenterDashboardApiController::class, 'permissions']);
});
