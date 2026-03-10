<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\WhatsappTrait;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\OrganizationActivationToken;
use Modules\Clinic\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class MedicalLaboratoryController extends Controller
{
    //
    use WhatsappTrait;

    public function index()
    {

        return view('backend.dashboards.admin.pages.medical-laboratories.index');
    }

    public function data()
    {

        $query = MedicalLaboratory::withCount(['users', 'patients'])
        ;

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

        $medicalLab = MedicalLaboratory::findOrFail($request->id);
        $medicalLab->status = $request->status === 'active' ? 1 : 0;
        $medicalLab->save();

        return response()->json([
            'status' => 'success',
            'message' => 'medica lLab status updated successfully'
        ]);
    }


    public function approveMedicalLaboratory($batchId)
    {
        try {
            DB::beginTransaction();

            // Fetch temp data records
            $medicalLaboratoryTemp = DB::table('temp_data')
                ->where('batch_id', $batchId)
                ->where('type', 'medicalLaboratory')
                ->first();

            $userTemp = DB::table('temp_data')
                ->where('batch_id', $batchId)
                ->where('type', 'user')
                ->first();

            if (!$medicalLaboratoryTemp || !$userTemp) {
                return response()->json(['success' => false, 'message' => 'Invalid batch data.'], 404);
            }

            $medicalLaboratoryData = json_decode($medicalLaboratoryTemp->data, true);
            $userData = json_decode($userTemp->data, true);

            $medicalLaboratoryData['status'] = 1 ;

            // Create medicalLaboratory
            $medicalLaboratory = MedicalLaboratory::create($medicalLaboratoryData);

            // Create user
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'organization_id' => $medicalLaboratory->id,
                'organization_type' => MedicalLaboratory::class,
            ]);

            $role = Role::where('name', 'medical-laboratory-admin')
            ->where('guard_name', 'medical_laboratory')
            ->first();

            $user->assignRole($role);

            // Optional: Send activation
            $token = Str::random(10);
            OrganizationActivationToken::create([
                'organization_id' => $medicalLaboratory->id,
                'organization_type' => MedicalLaboratory::class,
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHours(24),
            ]);

            $this->sendWhatsAppActivationNotification($medicalLaboratory, $user, $token);

            // Clean up temp data
            DB::table('temp_data')->where('batch_id', $batchId)->delete();

            DB::commit();


            return redirect()->back()->with('toast_success', 'Medical Laboratory approved and activated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('toast_error', 'Approval failed');
        }
    }
}
