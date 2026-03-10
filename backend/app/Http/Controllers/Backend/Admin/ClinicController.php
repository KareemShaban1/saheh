<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\WhatsappTrait;
use App\Models\Clinic;
use App\Models\OrganizationActivationToken;
use Modules\Clinic\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ClinicController extends Controller
{
    use WhatsappTrait;

    //
    public function index()
    {

        return view('backend.dashboards.admin.pages.clinics.index');
    }

    public function data()
    {

        $query = Clinic::withCount(['users', 'doctors', 'patients']) // Laravel's withCount() correctly calculates counts
        ;

        return DataTables::of($query)
            ->addColumn('users_count', function ($item) {
                return $item->users_count ?? 0;
            })
            ->addColumn('doctors_count', function ($item) {
                return $item->doctors_count ?? 0;
            })
            ->addColumn('patients_count', function ($item) {
                return $item->patients_count ?? 0;
            })

            ->addColumn('action', function ($item) {
                // $btn = '<div class="d-flex gap-2">';

                // if (auth()->user()->can('update clinic')) {
                //     $btn .= '<a href="javascript:void(0);" onclick="editClinic(' . $item->id . ', \'' . addslashes($item->name) . '\')"
                //                 class="btn btn-sm btn-info">
                //                     <i class="mdi mdi-square-edit-outline"></i>
                //                 </a>';
                // }

                // if (auth()->user()->can('delete clinic')) {
                //     $btn .= '<a href="javascript:void(0);" onclick="deleteClinic(' . $item->id . ')"
                //                 class="btn btn-sm btn-danger">
                //                     <i class="mdi mdi-delete"></i>
                //                 </a>';
                // }

                // return !empty(trim($btn)) ? $btn . '</div>' : '';
            })
            ->addColumn('status', function ($item) {
                $checked = $item->status === 1 ? 'checked' : '';
                $status = $item->status === 1 ? 'active' : 'inactive';
                return '
               <label class="switch">
            <input type="checkbox" class="toggle-status" data-id="' . $item->id . '" ' . $checked . '>
            <span class="slider round"></span>
        </label>
            ';
            })
            ->editColumn('created_at', function ($item) {
                return $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required|in:active,inactive'
        ]);

        $clinic = Clinic::findOrFail($request->id);
        $clinic->status = $request->status === 'active' ? 1 : 0;
        $clinic->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Clinic status updated successfully'
        ]);
    }


    public function approveClinic($batchId)
    {
        try {
            DB::beginTransaction();

            // Fetch temp data records
            $clinicTemp = DB::table('temp_data')
                ->where('batch_id', $batchId)
                ->where('type', 'clinic')
                ->first();

            $userTemp = DB::table('temp_data')
                ->where('batch_id', $batchId)
                ->where('type', 'user')
                ->first();

            if (!$clinicTemp || !$userTemp) {
                return response()->json(['success' => false, 'message' => 'Invalid batch data.'], 404);
            }

            $clinicData = json_decode($clinicTemp->data, true);
            $userData = json_decode($userTemp->data, true);

            $clinicData['status'] = 1;

            // Create clinic
            $clinic = Clinic::create($clinicData);

            // Create user
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'organization_id' => $clinic->id,
                'organization_type' => Clinic::class,
            ]);

            $user->assignRole('clinic-admin');

            // Optional: Send activation
            $token = Str::random(10);
            OrganizationActivationToken::create([
                'organization_id' => $clinic->id,
                'organization_type' => Clinic::class,
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHours(24),
            ]);

            $this->sendWhatsAppActivationNotification($clinic, $user, $token);

            // Clean up temp data
            DB::table('temp_data')->where('batch_id', $batchId)->delete();

            DB::commit();


            return redirect()->back()->with('toast_success', 'Clinic approved and activated successfully');
            // return response()->json(['success' => true, 'message' => 'Clinic approved and activated.']);

        } catch (\Exception $e) {
            DB::rollBack();
            // return response()->json(['success' => false, 'message' => 'Approval failed.', 'error' => $e->getMessage()], 500);
            return redirect()->back()->with('toast_error', 'Approval failed');
        }
    }
}
