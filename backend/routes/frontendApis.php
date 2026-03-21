<?php

use App\Http\Controllers\FrontApis\OrganizationAuthApiController;
use App\Http\Controllers\FrontApis\OrganizationChatApiController;
use App\Http\Controllers\FrontApis\AdminAuthApiController;
use App\Http\Controllers\FrontApis\AdminDashboardApiController;
use App\Http\Controllers\FrontApis\clinic\ClinicDashboardApiController;
use App\Http\Controllers\FrontApis\medicalLaboratory\MedicalLaboratoryDashboardApiController;
use App\Http\Controllers\FrontApis\radiologyCenter\RadiologyCenterDashboardApiController;
use App\Http\Controllers\FrontApis\clinic\ReservationController;
use App\Http\Controllers\FrontApis\clinic\DoctorController;
use App\Http\Controllers\FrontApis\clinic\DrugController;
use App\Http\Controllers\FrontApis\clinic\PatientController;
use App\Http\Controllers\FrontApis\clinic\RolePermissionController;
use App\Http\Controllers\FrontApis\clinic\ServiceController;
use App\Http\Controllers\FrontApis\organization\QuestionnaireController;
use App\Http\Controllers\FrontApis\organization\OrganizationMediaController;

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
Route::prefix('organization/media')->middleware('auth:organization_api')->group(function () {
    Route::get('/', [OrganizationMediaController::class, 'index']);
    Route::post('/', [OrganizationMediaController::class, 'store']);
    Route::delete('/{id}', [OrganizationMediaController::class, 'destroy']);
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
    
Route::get('/reservations/data', [ReservationController::class, 'reservationsData']);
    Route::get('/reservations/options', [ReservationController::class, 'reservationOptions']);
    Route::get('/reservations/{id}', [ReservationController::class, 'reservationDetails']);
    Route::get('/reservations/{id}/sessions-context', [ReservationController::class, 'sessionContext']);
    Route::post('/reservations', [ReservationController::class, 'createReservation']);
    Route::put('/reservations/{id}', [ReservationController::class, 'updateReservation']);
    Route::post('/reservations/{id}/sessions', [ReservationController::class, 'createSession']);
    Route::get('/reservations/{id}/prescription', [ReservationController::class, 'reservationPrescription']);
    Route::post('/reservations/{id}/prescription', [ReservationController::class, 'saveReservationPrescription']);
    Route::get('/reservations/{id}/glasses-distances', [ReservationController::class, 'reservationGlassesDistances']);
    Route::post('/reservations/{id}/glasses-distances', [ReservationController::class, 'createReservationGlassesDistance']);
    Route::get('/reservations/{id}/teeth', [ReservationController::class, 'reservationTeeth']);
    Route::post('/reservations/{id}/teeth', [ReservationController::class, 'saveReservationTeeth']);
    Route::get('/reservations/{id}/rays', [ReservationController::class, 'reservationRays']);
    Route::post('/reservations/{id}/rays', [ReservationController::class, 'createReservationRay']);
   
    Route::get('/doctors', [DoctorController::class, 'doctors']);
    Route::get('/doctors/{id}', [DoctorController::class, 'doctorDetails']);
    Route::post('/doctors', [DoctorController::class, 'createDoctor']);
    Route::put('/doctors/{id}', [DoctorController::class, 'updateDoctor']);
    Route::get('/doctors/{doctorId}/services', [DoctorController::class, 'doctorServices']);
   
    Route::get('/specialties', [ClinicDashboardApiController::class, 'specialties']);
   
    Route::get('/patients', [PatientController::class, 'patients']);
    Route::get('/patients/{id}', [PatientController::class, 'patientDetails']);
    Route::get('/patients/{id}/history', [PatientController::class, 'patientHistory']);
    Route::post('/patients', [PatientController::class, 'createPatient']);
    Route::post('/patients/assign', [PatientController::class, 'assignPatientByCode']);
    Route::put('/patients/{id}', [PatientController::class, 'updatePatient']);
    Route::get('/patients/{id}/glasses-distances', [PatientController::class, 'patientGlassesDistances']);
    Route::post('/patients/{id}/glasses-distances', [PatientController::class, 'createPatientGlassesDistance']);
    
    Route::get('/roles', [RolePermissionController::class, 'roles']);
    Route::get('/roles/{id}', [RolePermissionController::class, 'roleDetails']);
    Route::post('/roles', [RolePermissionController::class, 'createRole']);
    Route::put('/roles/{id}', [RolePermissionController::class, 'updateRole']);
    Route::get('/permissions', [RolePermissionController::class, 'permissions']);
    

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
    
    Route::get('/services', [ServiceController::class, 'services']);
    Route::get('/services/{id}', [ServiceController::class, 'serviceDetails']);
    Route::get('/services/{id}/instructions', [ServiceController::class, 'serviceInstructions']);
    Route::post('/services', [ServiceController::class, 'createService']);
    Route::put('/services/{id}', [ServiceController::class, 'updateService']);

    Route::get('/drugs', [DrugController::class, 'drugs']);
    Route::get('/drugs/{id}', [DrugController::class, 'drugDetails']);
    Route::post('/drugs', [DrugController::class, 'createDrug']);
    Route::put('/drugs/{id}', [DrugController::class, 'updateDrug']);
    Route::delete('/drugs/{id}', [DrugController::class, 'deleteDrug']);

    Route::get('/questionnaires', [QuestionnaireController::class, 'index']);
    Route::get('/questionnaires/{id}', [QuestionnaireController::class, 'show']);
    Route::post('/questionnaires', [QuestionnaireController::class, 'store']);
    Route::put('/questionnaires/{id}', [QuestionnaireController::class, 'update']);
    Route::delete('/questionnaires/{id}', [QuestionnaireController::class, 'destroy']);
    Route::get('/questionnaires/{id}/answers', [QuestionnaireController::class, 'answers']);
    
Route::get('/settings', [ClinicDashboardApiController::class, 'clinicSettings']);
    Route::put('/settings', [ClinicDashboardApiController::class, 'updateClinicSettings']);
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

    Route::get('/questionnaires', [QuestionnaireController::class, 'index']);
    Route::get('/questionnaires/{id}', [QuestionnaireController::class, 'show']);
    Route::post('/questionnaires', [QuestionnaireController::class, 'store']);
    Route::put('/questionnaires/{id}', [QuestionnaireController::class, 'update']);
    Route::delete('/questionnaires/{id}', [QuestionnaireController::class, 'destroy']);
    Route::get('/questionnaires/{id}/answers', [QuestionnaireController::class, 'answers']);
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
    Route::get('/ray-categories', [RadiologyCenterDashboardApiController::class, 'rayCategories']);
    Route::get('/ray-categories/{id}', [RadiologyCenterDashboardApiController::class, 'rayCategoryDetails']);
    Route::post('/ray-categories', [RadiologyCenterDashboardApiController::class, 'createRayCategory']);
    Route::put('/ray-categories/{id}', [RadiologyCenterDashboardApiController::class, 'updateRayCategory']);
    Route::delete('/ray-categories/{id}', [RadiologyCenterDashboardApiController::class, 'deleteRayCategory']);
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

    Route::get('/questionnaires', [QuestionnaireController::class, 'index']);
    Route::get('/questionnaires/{id}', [QuestionnaireController::class, 'show']);
    Route::post('/questionnaires', [QuestionnaireController::class, 'store']);
    Route::put('/questionnaires/{id}', [QuestionnaireController::class, 'update']);
    Route::delete('/questionnaires/{id}', [QuestionnaireController::class, 'destroy']);
    Route::get('/questionnaires/{id}/answers', [QuestionnaireController::class, 'answers']);
});