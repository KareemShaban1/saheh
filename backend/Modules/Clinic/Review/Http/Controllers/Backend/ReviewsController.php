<?php

namespace Modules\Clinic\Review\Http\Controllers\Backend;

use App\Models\Shared\PatientReview;
use App\Http\Traits\AuthorizeCheck;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ReviewsController extends Controller
{
    use AuthorizeCheck;

    public function index()
    {
        $this->authorizeCheck('view-reviews');
        return view('backend.dashboards.clinic.pages.reviews.index');
    }

    public function data()
    {
        $this->authorizeCheck('view-reviews');

        $query = PatientReview::with([
            'patient:id,name',
            'doctor.user:id,name'
        ])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('organization_type', 'App\Models\Clinic');

        return DataTables::of($query)
            ->addColumn('doctor_name', function ($item) {
                return $item->doctor && $item->doctor->user ? $item->doctor->user->name : 'N/A';
            })
            ->addColumn('patient_name', function ($item) {
                return $item->patient ? $item->patient->name : 'N/A';
            })
            ->editColumn('created_at', function ($item) {
                return $item->created_at->format('Y-m-d H:i:s');
            })
            ->make(true);
    }
}
