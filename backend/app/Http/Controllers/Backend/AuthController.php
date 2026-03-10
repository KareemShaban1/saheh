<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\StoreMedicalLaboratoryRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Http\Traits\WhatsappTrait;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use Modules\Clinic\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Models\OrganizationActivationToken;
use App\Models\RadiologyCenter;
use App\Http\Requests\StoreRadiologyCenterRequest;

class AuthController extends Controller
{
    use WhatsappTrait;

    public function registerClinic(Request $request)
    {

        // Validate input
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'user_email' => 'required|email|unique:users,email',
            'clinic_email' => 'required|email|unique:clinics,email',
            'password' => 'required|string|min:6|confirmed',
            'governorate_id' => 'nullable|exists:governorates,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'website' => 'nullable|url',
            'domain' => 'nullable|string',
            'database' => 'nullable|string',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'latitude'=> 'nullable|string',
            'longitude' => 'nullable|string',
        ]);


        // Create the clinic
        $clinic = Clinic::create([
            'name' => $validated['clinic_name'],
            'start_date' => $validated['start_date'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'email' => $validated['clinic_email'],
            'governorate_id' => $validated['governorate_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'area_id' => $validated['area_id'] ?? null,
            'website' => $validated['website'] ?? null,
            'domain' => $validated['domain'] ?? null,
            'database' => $validated['database'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'status' => 0, // Active by default
            'specialty_id' => $request->specialty_id,
            'latitude' =>  $validated['latitude'] ?? null,
            'longitude' =>  $validated['longitude'] ?? null,
        ]);


        // Create the clinic admin user
        $user = User::create([
            'organization_id' => $clinic->id,
            'organization_type' => Clinic::class,
            'name' => 'Clinic Admin', // You can also take this from input
            'email' => $validated['user_email'],
            'password' => Hash::make($validated['password']),
        ]);



        // Assign "clinic-admin" role (if using Spatie Roles & Permissions)
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('clinic-admin');
        }

        // Login the user
        // Auth::login($user);

        return redirect()->to('/clinic/login')->with('success', 'Clinic registered successfully');
    }


    public function registerMedicalLaboratory(StoreMedicalLaboratoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            // Create the medical laboratory with is_active = false
            $medicalLab = MedicalLaboratory::create([
                'name' => $request->medical_laboratory_name,
                'start_date' => $request->start_date,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->medical_laboratory_email,
                'governorate_id' => $request->governorate_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'status' => 0, // Inactive until activated
                'latitude' =>  $validated['latitude'] ?? null,
                'longitude' =>  $validated['longitude'] ?? null,
            ]);

            // Create the admin user
            $user = User::create([
                'name' => $request->user_name,
                'email' => $request->user_email,
                'password' => Hash::make($request->password),
                'organization_id' => $medicalLab->id,
                'organization_type' => MedicalLaboratory::class,
            ]);

            $user->assignRole('medical-laboratory-admin');


            // Generate activation token
            $token = Str::random(10);
            $activationToken = OrganizationActivationToken::create([
                'organization_id' => $medicalLab->id,
                'organization_type' => MedicalLaboratory::class,
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHours(24), // Token expires in 24 hours
            ]);

            // Send WhatsApp activation notification
            $this->sendWhatsAppMedicalLabActivationNotification($medicalLab, $user, $token);

            DB::commit();

            Log::info('Medical Laboratory registered successfully', [
                'medical_lab_id' => $medicalLab->id,
                'medical_lab_name' => $medicalLab->name,
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Medical Laboratory registered successfully! Please check your WhatsApp for the activation link.')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Medical Laboratory registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Registration failed. Please try again.'),
                'debug_message' => $e->getMessage() // Only in development
            ], 500);
        }
    }

    /**
     * Activate medical laboratory account
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function activateMedicalLaboratory($token): RedirectResponse
    {
        $activationToken = OrganizationActivationToken::where('token', $token)->first();

        if (!$activationToken) {
            return redirect()->to('/medical-laboratory/login')->with('error', 'Invalid activation token.');
        }

        if ($activationToken->isExpired()) {
            return redirect()->to('/medical-laboratory/login')->with('error', 'Activation token has expired.');
        }

        if ($activationToken->isUsed()) {
            return redirect()->to('/medical-laboratory/login')->with('error', 'Activation token has already been used.');
        }

        try {
            DB::beginTransaction();

            // Activate the organization
            $organization = $activationToken->organization;
            $organization->update(['status' => 1]);

            // Mark token as used
            $activationToken->update(['used_at' => now()]);

            Log::info('Medical Laboratory activated successfully', [
                'token' => $token,
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'organization_email' => $organization->email,
                'organization_phone' => $organization->phone,
                'organization_address' => $organization->address,
                'organization_type' => get_class($organization),
            ]);

            DB::commit();

            return redirect()->to('/medical-laboratory/login')->with('success', 'Medical Laboratory activated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->to('/medical-laboratory/login')->with('error', 'Medical Laboratory activation failed');
        }
    }

    public function registerPatient(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:patients,email',
            'password' => 'required|string|min:6',
            'age' => 'required|numeric',
            'phone' => 'required|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'address' => 'required|string',
            'gender' => 'required|in:male,female',
            'blood_group' => 'required|in:A+,A-,B+,B-,O+,O-,AB+,AB-',

        ]);



        $patient = Patient::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'age' => $request->age,
            'phone' => $request->phone,
            'address' => $request->address,
            'gender' => $request->gender,
            'blood_group' => $request->blood_group,
            'whatsapp_number' => $request->whatsapp_number,
        ]);

        return redirect()->to('/patient/login')->with('success', 'Patient registered successfully');
    }


    public function registerRadiologyCenter(StoreRadiologyCenterRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create the radiology center with is_active = false
            $radiologyCenter = RadiologyCenter::create([
                'name' => $request->radiology_center_name,
                'start_date' => $request->start_date,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->radiology_center_email,
                'governorate_id' => $request->governorate_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'status' => 0, // Inactive until activated
                'latitude' =>  $validated['latitude'] ?? null,
                'longitude' =>  $validated['longitude'] ?? null,
            ]);

            // Create the admin user
            $user = User::create([
                'name' => $request->user_name,
                'email' => $request->user_email,
                'password' => Hash::make($request->password),
                'organization_id' => $radiologyCenter->id,
                'organization_type' => RadiologyCenter::class,
            ]);

            $user->assignRole('radiology-center-admin');

            // Generate activation token
            $token = Str::random(10);
            $activationToken = OrganizationActivationToken::create([
                'organization_id' => $radiologyCenter->id,
                'organization_type' => RadiologyCenter::class,
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHours(24), // Token expires in 24 hours
            ]);

            // Send WhatsApp activation notification
            $this->sendWhatsAppRadiologyCenterActivationNotification($radiologyCenter, $user, $token);

            DB::commit();

            Log::info('Radiology Center registered successfully', [
                'radiology_center_id' => $radiologyCenter->id,
                'radiology_center_name' => $radiologyCenter->name,
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Radiology Center registered successfully! Please check your WhatsApp for the activation link.')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Radiology Center registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Registration failed. Please try again.'),
                'debug_message' => $e->getMessage() // Only in development
            ], 500);
        }
    }

    /**
     * Activate radiology center account
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function activateRadiologyCenter($token): RedirectResponse
    {
        $activationToken = OrganizationActivationToken::where('token', $token)->first();

        if (!$activationToken) {
            return redirect()->to('/radiology-center/login')->with('error', 'Invalid activation token.');
        }

        if ($activationToken->isExpired()) {
            return redirect()->to('/radiology-center/login')->with('error', 'Activation token has expired.');
        }

        if ($activationToken->isUsed()) {
            return redirect()->to('/radiology-center/login')->with('error', 'Activation token has already been used.');
        }

        try {
            DB::beginTransaction();

            // Activate the organization
            $organization = $activationToken->organization;
            $organization->update(['status' => 1]);

            // Mark token as used
            $activationToken->update(['used_at' => now()]);

            Log::info('Radiology Center activated successfully', [
                'token' => $token,
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'organization_email' => $organization->email,
                'organization_phone' => $organization->phone,
                'organization_address' => $organization->address,
                'organization_type' => get_class($organization),
            ]);

            DB::commit();

            return redirect()->to('/radiology-center/login')->with('success', 'Radiology Center activated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->to('/radiology-center/login')->with('error', 'Radiology Center activation failed');
        }
    }
}

