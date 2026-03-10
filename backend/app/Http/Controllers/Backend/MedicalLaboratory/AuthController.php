<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

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
use App\Models\TempData;
use App\Notifications\OrganizationRegistredNotification;

class AuthController extends Controller
{
    use WhatsappTrait;


    public function storeMedicalLaboratoryTempData(StoreMedicalLaboratoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $batchId = (string) Str::uuid(); // unique key to group records

            // Save temp clinic
            $insertedId = DB::table('temp_data')->insertGetId([
                'type' => 'medicalLaboratory',
                'batch_id' => $batchId,
                'data' => json_encode([
                    'name' => $request->medical_laboratory_name,
                    'start_date' => $request->start_date,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'email' => $request->medical_laboratory_email,
                    'governorate_id' => $request->governorate_id,
                    'city_id' => $request->city_id,
                    'area_id' => $request->area_id,
                    'status' => 0,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Fetch the inserted temp_data record
            $OrgTempData = TempData::find($insertedId);

            // Notify all admins
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new OrganizationRegistredNotification($OrgTempData));
            }

            // Save temp user
            DB::table('temp_data')->insert([
                'type' => 'user',
                'batch_id' => $batchId,
                'data' => json_encode([
                    'name' => $request->user_name,
                    'email' => $request->user_email,
                    'password' => Hash::make($request->password),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clinic data submitted for review. You will be contacted upon approval.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Submission failed.',
                'error' => $e->getMessage()
            ], 500);
        }
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
}
