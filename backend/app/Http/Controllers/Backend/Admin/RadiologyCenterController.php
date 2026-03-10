<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\OrganizationActivationToken;
use App\Models\RadiologyCenter;
use Modules\Clinic\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RadiologyCenterController extends Controller
{
    //
    public function index()
    {

        return view('backend.dashboards.admin.pages.radiology-centers.index');
    }

    public function data()
    {

        $query = RadiologyCenter::withCount(['users', 'patients']);

        return DataTables::of($query)
            ->addColumn('users_count', function ($item) {
                return $item->users_count ?? 0;
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

        $radiologyCenter = RadiologyCenter::findOrFail($request->id);
        $radiologyCenter->status = $request->status === 'active' ? 1 : 0;
        $radiologyCenter->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Radiology Center status updated successfully'
        ]);
    }

    public function approveRadiologyCenter($batchId)
    {
        try {
            DB::beginTransaction();

            // Fetch temp data records
            $radiologyCenterTemp = DB::table('temp_data')
                ->where('batch_id', $batchId)
                ->where('type', 'radiologyCenter')
                ->first();

            $userTemp = DB::table('temp_data')
                ->where('batch_id', $batchId)
                ->where('type', 'user')
                ->first();

            if (!$radiologyCenterTemp || !$userTemp) {
                return response()->json(['success' => false, 'message' => 'Invalid batch data.'], 404);
            }

            $radiologyCenterData = json_decode($radiologyCenterTemp->data, true);
            $userData = json_decode($userTemp->data, true);

            $radiologyCenterData['status'] = 1 ;

            // Create radiologyCenter
            $radiologyCenter = RadiologyCenter::create($radiologyCenterData);

            // Create user
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'organization_id' => $radiologyCenter->id,
                'organization_type' => RadiologyCenter::class,
            ]);

            $role = Role::where('name', 'radiology-center-admin')
            ->where('guard_name', 'radiology_center')
            ->first();

            $user->assignRole($role);

            // Optional: Send activation
            $token = Str::random(10);
            OrganizationActivationToken::create([
                'organization_id' => $radiologyCenter->id,
                'organization_type' => RadiologyCenter::class,
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHours(24),
            ]);

            $this->sendWhatsAppActivationNotification($radiologyCenter, $user, $token);

            // Clean up temp data
            DB::table('temp_data')->where('batch_id', $batchId)->delete();

            DB::commit();


            return redirect()->back()->with('toast_success', 'Radiology Center approved and activated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('toast_error', 'Approval failed');
        }
    }
}
