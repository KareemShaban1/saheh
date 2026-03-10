<?php

namespace App\Http\Controllers\Backend\MedicalLaboratory;

use App\Http\Controllers\Controller;
use App\Models\Shared\PatientReview;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ReviewsController extends Controller
{
    public function index()
    {
        return view('backend.dashboards.medicalLaboratory.pages.reviews.index');
    }

    public function data()
    {
        $query = PatientReview::with(['patient:id,name', 'doctor:id,name'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('organization_type', 'App\Models\MedicalLaboratory');

        return DataTables::of($query)
            ->addColumn('doctor_name', function ($item) {
                return $item->doctor ? $item->doctor->name : 'N/A';
            })
            ->addColumn('patient_name', function ($item) {
                return $item->patient ? $item->patient->name : 'N/A';
            })
            ->editColumn('created_at', function($item) {
                return $item->created_at->format('Y-m-d H:i:s');
            })
            ->make(true);
    }
}
