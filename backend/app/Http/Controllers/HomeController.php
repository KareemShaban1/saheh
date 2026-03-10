<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\TimeSlotsTrait;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use App\Models\RadiologyCenter;
use Modules\Clinic\Reservation\Models\Reservation;
use Modules\Clinic\ReservationSlot\Models\ReservationSlots;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    use TimeSlotsTrait;

    //
    public function index()
    {
        $patientsCount = Patient::count();
        $doctorsCount = Doctor::count();
        $clinicsCount = Clinic::count();
        $medicalLabsCount = MedicalLaboratory::count();
        $radiologyCentersCount = RadiologyCenter::count();
        $reservationsCount = Reservation::count();

        $featuredClinics = Clinic::with(['specialty', 'governorate', 'city'])
            ->withAvg('reviews', 'rating')
            ->take(6)
            ->get();

        $featuredMedicalLaboratories = MedicalLaboratory::with(['governorate', 'city'])
            ->withAvg('reviews', 'rating')
            ->take(6)
            ->get();

        $featuredRadiologyCenters = RadiologyCenter::with(['governorate', 'city'])
            ->withAvg('reviews', 'rating')
            ->take(6)
            ->get();

        return view('frontend.pages.home.index', compact(
            'patientsCount',
            'doctorsCount',
            'clinicsCount',
            'medicalLabsCount',
            'radiologyCentersCount',
            'reservationsCount',
            'featuredClinics',
            'featuredMedicalLaboratories',
            'featuredRadiologyCenters'
        ));
    }

    public function clinics()
    {
        $clinics = Clinic::with(['specialty', 'governorate', 'city', 'area', 'doctors.user'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();

        $areaIds = $clinics->pluck('area_id')->unique()->filter()->values();
        $areas = \App\Models\Area::whereIn('id', $areaIds)->orderBy('name')->get();

        return view('frontend.pages.clinics', compact('clinics', 'areas'));
    }

    public function medicalLaboratories()
    {
        $medicalLaboratories = MedicalLaboratory::with(['governorate', 'city', 'area'])->get();
        return view('frontend.pages.medical-laboratories', compact('medicalLaboratories'));
    }

    public function radiologyCenters()
    {
        $radiologyCenters = RadiologyCenter::with(['governorate', 'city', 'area'])->get();
        return view('frontend.pages.radiology-centers', compact('radiologyCenters'));
    }

    public function clinicDetail($id)
    {
        $clinic = Clinic::with([
            'specialty',
            'governorate',
            'city',
            'area',
            'doctors.user',
            'doctors.specialty',
            'reviews.patient'
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->findOrFail($id);

        $nearbyClinics = Clinic::with(['specialty', 'governorate', 'city'])
            ->withAvg('reviews', 'rating')
            ->where('id', '!=', $clinic->id)
            ->when($clinic->area_id, fn ($q) => $q->where('area_id', $clinic->area_id))
            ->take(3)
            ->get();

        return view('frontend.pages.clinic-detail', compact('clinic', 'nearbyClinics'));
    }

    public function doctorDetail($id)
    {
        $doctor = Doctor::with([
            'user',
            'clinic',
            'specialty',
            'reviews'
        ])->findOrFail($id);

        return view('frontend.pages.doctor-detail', compact('doctor'));
    }

    public function medicalLaboratoryDetail($id)
    {
        $medicalLaboratory = MedicalLaboratory::with([
            'governorate',
            'city',
            'area',
            'reviews'
        ])->findOrFail($id);

        return view('frontend.pages.medical-laboratory-detail', compact('medicalLaboratory'));
    }

    public function radiologyCenterDetail($id)
    {
        $radiologyCenter = RadiologyCenter::with([
            'governorate',
            'city',
            'area',
            'reviews'
        ])->findOrFail($id);

        return view('frontend.pages.radiology-center-detail', compact('radiologyCenter'));
    }

}