<?php

namespace App\Http\Controllers\Backend\Clinic;

use Modules\Clinic\User\Models\User;
use App\Models\Clinic;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Traits\WhatsappTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\OrganizationActivationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\StoreClinicRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Models\Admin;
use App\Models\TempData;
use App\Notifications\ClinicActivationNotification;
use App\Notifications\OrganizationRegistredNotification;

class AuthController extends Controller
{
    use WhatsappTrait;

    public function create()
    {
        //
        return view('backend.dashboards.clinic.auth.register-clinic');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreClinicRequest  $request
     * @return \Illuminate\Http\Response
     */

    public function storeClinicTempData(StoreClinicRequest $request)
    {
        try {
            DB::beginTransaction();

            $batchId = (string) Str::uuid(); // unique key to group records
            // Save temp clinic
            $insertedId = DB::table('temp_data')->insertGetId([
                'type' => 'clinic',
                'batch_id' => $batchId,
                'data' => json_encode([
                    'name' => $request->clinic_name,
                    'start_date' => $request->start_date,
                    'specialty_id' => $request->specialty_id,
                    'governorate_id' => $request->governorate_id,
                    'city_id' => $request->city_id,
                    'area_id' => $request->area_id,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'email' => $request->clinic_email,
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
            \Log::error('errors' , [$e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Submission failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(StoreClinicRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create the clinic with is_active = false
            $clinic = Clinic::create([
                'name' => $request->clinic_name,
                'start_date' => $request->start_date,
                'specialty_id' => $request->specialty_id,
                'governorate_id' => $request->governorate_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->clinic_email,
                'status' => 0,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // Create the user
            $user = User::create([
                'name' => $request->user_name,
                'email' => $request->user_email,
                'password' => Hash::make($request->password),
                'organization_id' => $clinic->id,
                'organization_type' => Clinic::class,
            ]);

            $user->assignRole('clinic-admin');


            // Generate activation token
            $token = Str::random(10);
            $activationToken = OrganizationActivationToken::create([
                'organization_id' => $clinic->id,
                'organization_type' => Clinic::class,
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHours(24), // Token expires in 24 hours
            ]);


            // Send WhatsApp activation notification
            $this->sendWhatsAppActivationNotification($clinic, $user, $token);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clinic registered successfully! Please check your WhatsApp for the activation link.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Activate clinic account
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function activate($token): RedirectResponse
    {
        $activationToken = OrganizationActivationToken::where('token', $token)->first();

        if (!$activationToken) {
            return redirect()->to('/clinic/login')->with('error', 'Invalid activation token.');
        }

        if ($activationToken->isExpired()) {
            return redirect()->to('/clinic/login')->with('error', 'Activation token has expired.');
        }

        if ($activationToken->isUsed()) {
            return redirect()->to('/clinic/login')->with('error', 'Activation token has already been used.');
        }

        try {
            DB::beginTransaction();

            // Activate the organization
            $organization = $activationToken->organization;
            $organization->update(['status' => 1]);

            // Mark token as used
            $activationToken->update(['used_at' => now()]);

            Log::info('Organization activated successfully', [
                'token' => $token,
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'organization_email' => $organization->email,
                'organization_phone' => $organization->phone,
                'organization_address' => $organization->address,
                'organization_type' => get_class($organization),
            ]);

            DB::commit();

            return redirect()->to('/clinic/login')->with('success', 'Organization activated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->to('/clinic/login')->with('error', 'Organization activation failed');
        }
    }
}
