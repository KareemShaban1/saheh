<?php

namespace App\Http\Controllers\FrontApis;

use App\Http\Controllers\Controller;
use App\Traits\ApiHelperTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use App\Models\Specialty;
use App\Models\Governorate;
use App\Models\City;
use App\Models\Area;
use App\Models\Shared\PatientReview;
use Modules\Clinic\Announcement\Models\Announcement;
use Modules\Clinic\Doctor\Models\Doctor;
use Modules\Clinic\Medicine\Models\Medicine;
use App\Models\OnlineReservation;
use Modules\Clinic\Reservation\Models\Reservation;
use App\Models\MedicalAnalysis;
use App\Models\Ray;
use App\Models\Scopes\ClinicScope;
use Modules\Clinic\User\Models\User;
use Spatie\Permission\Models\Role;

class AdminDashboardApiController extends Controller
{
    use ApiHelperTrait;

    public function dashboard()
    {
        $this->ensureAdminAuth();
        $doctors_count = Doctor::withoutGlobalScope(ClinicScope::class)->count();
        $patients_count = \App\Models\Shared\Patient::count();
        $medicines_count = Medicine::count();
        $online_reservations_count = OnlineReservation::count();
        $all_reservations_count = Reservation::withoutGlobalScope(ClinicScope::class)->count();
        $data = [
            'doctors_count' => $doctors_count,
            'patients_count' => $patients_count,
            'medicines_count' => $medicines_count,
            'online_reservations_count' => $online_reservations_count,
            'all_reservations_count' => $all_reservations_count,
        ];
        return $this->returnJSON($data, 'Dashboard data', 'success');
    }

    /**
     * Financial module data across all organizations.
     */
    public function financial(Request $request)
    {
        $this->ensureAdminAuth();

        $months = max(3, min(12, (int) $request->get('months', 6)));
        $from = Carbon::now('Egypt')->startOfMonth()->subMonths($months - 1);

        $clinicBase = Reservation::withoutGlobalScope(ClinicScope::class);
        $labBase = MedicalAnalysis::withoutGlobalScope(\App\Models\Scopes\MedicalLaboratoryScope::class);
        $radiologyBase = Ray::withoutGlobalScope(\App\Models\Scopes\RadiologyCenterScope::class);

        $clinicPaid = (float) ((clone $clinicBase)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);
        $labPaid = (float) ((clone $labBase)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);
        $radiologyPaid = (float) ((clone $radiologyBase)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);

        $clinicDue = (float) ((clone $clinicBase)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='not_paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);
        $labDue = (float) ((clone $labBase)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='not_paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);
        $radiologyDue = (float) ((clone $radiologyBase)
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='not_paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END), 0) as total")
            ->value('total') ?? 0);

        $monthlyClinic = (clone $clinicBase)
            ->whereDate('date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END),0) as revenue")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');
        $monthlyLab = (clone $labBase)
            ->whereDate('date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END),0) as revenue")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');
        $monthlyRadiology = (clone $radiologyBase)
            ->whereDate('date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment='paid' THEN CAST(cost AS DECIMAL(12,2)) ELSE 0 END),0) as revenue")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $trend = [];
        for ($i = 0; $i < $months; $i++) {
            $pointDate = (clone $from)->addMonths($i);
            $key = $pointDate->format('Y-m');
            $clinicRevenue = (float) (($monthlyClinic->get($key)->revenue ?? 0));
            $labRevenue = (float) (($monthlyLab->get($key)->revenue ?? 0));
            $radiologyRevenue = (float) (($monthlyRadiology->get($key)->revenue ?? 0));
            $trend[] = [
                'month' => $pointDate->format('M Y'),
                'clinic' => $clinicRevenue,
                'lab' => $labRevenue,
                'radiology' => $radiologyRevenue,
                'revenue' => $clinicRevenue + $labRevenue + $radiologyRevenue,
            ];
        }

        return $this->returnJSON([
            'summary' => [
                'total_revenue' => $clinicPaid + $labPaid + $radiologyPaid,
                'total_due' => $clinicDue + $labDue + $radiologyDue,
                'paid_count' => (int) ((clone $clinicBase)->where('payment', 'paid')->count()) + (int) ((clone $labBase)->where('payment', 'paid')->count()) + (int) ((clone $radiologyBase)->where('payment', 'paid')->count()),
                'unpaid_count' => (int) ((clone $clinicBase)->where('payment', 'not_paid')->count()) + (int) ((clone $labBase)->where('payment', 'not_paid')->count()) + (int) ((clone $radiologyBase)->where('payment', 'not_paid')->count()),
            ],
            'trend' => $trend,
            'breakdown' => [
                ['name' => 'Clinic', 'value' => $clinicPaid],
                ['name' => 'Laboratory', 'value' => $labPaid],
                ['name' => 'Radiology', 'value' => $radiologyPaid],
            ],
        ], 'Financial data', 'success');
    }

    public function clinics(Request $request)
    {
        $this->ensureAdminAuth();
        $query = Clinic::withCount(['users', 'doctors', 'patients']);
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'users_count' => $c->users_count ?? 0,
            'doctors_count' => $c->doctors_count ?? 0,
            'patients_count' => $c->patients_count ?? 0,
            'status' => $c->status ?? 1,
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Clinics', 'success');
    }

    public function clinicDetails($id)
    {
        $this->ensureAdminAuth();
        $clinic = Clinic::with(['specialty:id,name_en,name_ar', 'governorate:id,name', 'city:id,name', 'area:id,name'])
            ->withCount(['users', 'doctors', 'patients'])
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $clinic->id,
            'name' => $clinic->name,
            'email' => $clinic->email,
            'phone' => $clinic->phone,
            'address' => $clinic->address,
            'website' => $clinic->website,
            'description' => $clinic->description,
            'status' => (int) ($clinic->status ?? 1),
            'is_active' => (int) ($clinic->is_active ?? 1),
            'specialty' => $clinic->specialty,
            'governorate' => $clinic->governorate,
            'city' => $clinic->city,
            'area' => $clinic->area,
            'users_count' => $clinic->users_count ?? 0,
            'doctors_count' => $clinic->doctors_count ?? 0,
            'patients_count' => $clinic->patients_count ?? 0,
            'created_at' => optional($clinic->created_at)->toDateTimeString(),
        ], 'Clinic details', 'success');
    }

    public function createClinic(Request $request)
    {
        $this->ensureAdminAuth();

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clinics,email',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'specialty_id' => 'nullable|integer|exists:specialties,id',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'area_id' => 'nullable|integer|exists:areas,id',
        ]);

        $clinic = Clinic::create([
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'description' => $payload['description'] ?? null,
            'website' => $payload['website'] ?? null,
            'specialty_id' => $payload['specialty_id'] ?? null,
            'governorate_id' => $payload['governorate_id'] ?? null,
            'city_id' => $payload['city_id'] ?? null,
            'area_id' => $payload['area_id'] ?? null,
            'status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0,
            'is_active' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0,
        ]);

        return $this->returnJSON([
            'id' => $clinic->id,
            'name' => $clinic->name,
            'status' => (int) ($clinic->status ?? 1),
        ], 'Clinic created successfully', 'success');
    }

    public function updateClinicStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();

        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $clinic = Clinic::findOrFail($id);
        $isActive = $payload['status'] === 'active';
        $clinic->status = $isActive ? 1 : 0;
        $clinic->is_active = $isActive ? 1 : 0;
        $clinic->save();

        return $this->returnJSON([
            'id' => $clinic->id,
            'status' => (int) $clinic->status,
        ], 'Clinic status updated', 'success');
    }

    public function deleteClinic($id)
    {
        $this->ensureAdminAuth();
        $clinic = Clinic::findOrFail($id);
        $clinic->delete();

        return $this->returnJSON([
            'id' => $id,
        ], 'Clinic deleted successfully', 'success');
    }

    public function medicalLabs(Request $request)
    {
        $this->ensureAdminAuth();
        $query = MedicalLaboratory::withCount(['users', 'patients']);
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name ?? 'N/A',
            'users_count' => $m->users_count ?? 0,
            'patients_count' => $m->patients_count ?? 0,
            'status' => $m->status ?? 1,
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Medical laboratories', 'success');
    }

    public function medicalLabDetails($id)
    {
        $this->ensureAdminAuth();
        $lab = MedicalLaboratory::with(['governorate:id,name', 'city:id,name', 'area:id,name'])
            ->withCount(['users', 'patients'])
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $lab->id,
            'name' => $lab->name,
            'email' => $lab->email,
            'phone' => $lab->phone,
            'address' => $lab->address,
            'website' => $lab->website,
            'description' => $lab->description,
            'status' => (int) ($lab->status ?? 1),
            'governorate' => $lab->governorate,
            'city' => $lab->city,
            'area' => $lab->area,
            'users_count' => $lab->users_count ?? 0,
            'patients_count' => $lab->patients_count ?? 0,
            'created_at' => optional($lab->created_at)->toDateTimeString(),
        ], 'Medical laboratory details', 'success');
    }

    public function createMedicalLab(Request $request)
    {
        $this->ensureAdminAuth();

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:medical_laboratories,email',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'area_id' => 'nullable|integer|exists:areas,id',
        ]);

        $lab = MedicalLaboratory::create([
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'description' => $payload['description'] ?? null,
            'website' => $payload['website'] ?? null,
            'governorate_id' => $payload['governorate_id'] ?? null,
            'city_id' => $payload['city_id'] ?? null,
            'area_id' => $payload['area_id'] ?? null,
            'status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0,
        ]);

        return $this->returnJSON([
            'id' => $lab->id,
            'name' => $lab->name,
            'status' => (int) ($lab->status ?? 1),
        ], 'Medical laboratory created successfully', 'success');
    }

    public function updateMedicalLabStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();

        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $lab = MedicalLaboratory::findOrFail($id);
        $lab->status = $payload['status'] === 'active' ? 1 : 0;
        $lab->save();

        return $this->returnJSON([
            'id' => $lab->id,
            'status' => (int) $lab->status,
        ], 'Medical laboratory status updated', 'success');
    }

    public function deleteMedicalLab($id)
    {
        $this->ensureAdminAuth();
        $lab = MedicalLaboratory::findOrFail($id);
        $lab->delete();

        return $this->returnJSON([
            'id' => $id,
        ], 'Medical laboratory deleted successfully', 'success');
    }

    public function radiologyCenters(Request $request)
    {
        $this->ensureAdminAuth();
        $query = RadiologyCenter::withCount(['users', 'patients']);
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name ?? 'N/A',
            'users_count' => $r->users_count ?? 0,
            'patients_count' => $r->patients_count ?? 0,
            'status' => $r->status ?? 1,
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Radiology centers', 'success');
    }

    public function radiologyCenterDetails($id)
    {
        $this->ensureAdminAuth();
        $center = RadiologyCenter::with(['governorate:id,name', 'city:id,name', 'area:id,name'])
            ->withCount(['users', 'patients'])
            ->findOrFail($id);

        return $this->returnJSON([
            'id' => $center->id,
            'name' => $center->name,
            'email' => $center->email,
            'phone' => $center->phone,
            'address' => $center->address,
            'website' => $center->website,
            'description' => $center->description,
            'status' => (int) ($center->status ?? 1),
            'governorate' => $center->governorate,
            'city' => $center->city,
            'area' => $center->area,
            'users_count' => $center->users_count ?? 0,
            'patients_count' => $center->patients_count ?? 0,
            'created_at' => optional($center->created_at)->toDateTimeString(),
        ], 'Radiology center details', 'success');
    }

    public function createRadiologyCenter(Request $request)
    {
        $this->ensureAdminAuth();

        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:radiology_centers,email',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'area_id' => 'nullable|integer|exists:areas,id',
        ]);

        $center = RadiologyCenter::create([
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'description' => $payload['description'] ?? null,
            'website' => $payload['website'] ?? null,
            'governorate_id' => $payload['governorate_id'] ?? null,
            'city_id' => $payload['city_id'] ?? null,
            'area_id' => $payload['area_id'] ?? null,
            'status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0,
        ]);

        return $this->returnJSON([
            'id' => $center->id,
            'name' => $center->name,
            'status' => (int) ($center->status ?? 1),
        ], 'Radiology center created successfully', 'success');
    }

    public function updateRadiologyCenterStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();

        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $center = RadiologyCenter::findOrFail($id);
        $center->status = $payload['status'] === 'active' ? 1 : 0;
        $center->save();

        return $this->returnJSON([
            'id' => $center->id,
            'status' => (int) $center->status,
        ], 'Radiology center status updated', 'success');
    }

    public function deleteRadiologyCenter($id)
    {
        $this->ensureAdminAuth();
        $center = RadiologyCenter::findOrFail($id);
        $center->delete();

        return $this->returnJSON([
            'id' => $id,
        ], 'Radiology center deleted successfully', 'success');
    }

    public function specialties(Request $request)
    {
        $this->ensureAdminAuth();
        $columns = ['id', 'name_en', 'name_ar', 'description'];
        if (Schema::hasColumn('specialties', 'status')) $columns[] = 'status';
        $items = Specialty::orderBy('id')->get($columns);
        $data = $items->map(fn ($s) => [
            'id' => $s->id,
            'name_en' => $s->name_en,
            'name_ar' => $s->name_ar,
            'description' => $s->description,
            'status' => (int) ($s->status ?? 1),
        ]);
        return $this->returnJSON($data, 'Specialties', 'success');
    }

    public function specialtyDetails($id)
    {
        $this->ensureAdminAuth();
        $specialty = Specialty::findOrFail($id);

        return $this->returnJSON([
            'id' => $specialty->id,
            'name_en' => $specialty->name_en,
            'name_ar' => $specialty->name_ar,
            'description' => $specialty->description,
            'status' => (int) ($specialty->status ?? 1),
        ], 'Specialty details', 'success');
    }

    public function createSpecialty(Request $request)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $specialty = Specialty::create([
            'name_en' => $payload['name_en'],
            'name_ar' => $payload['name_ar'],
            'description' => $payload['description'] ?? null,
            ...(Schema::hasColumn('specialties', 'status') ? ['status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0] : []),
        ]);

        return $this->returnJSON([
            'id' => $specialty->id,
            'name_en' => $specialty->name_en,
            'status' => (int) ($specialty->status ?? 1),
        ], 'Specialty created successfully', 'success');
    }

    public function updateSpecialty(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $specialty = Specialty::findOrFail($id);
        $specialty->update($payload);

        return $this->returnJSON([
            'id' => $specialty->id,
        ], 'Specialty updated successfully', 'success');
    }

    public function updateSpecialtyStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();
        if (!Schema::hasColumn('specialties', 'status')) {
            abort(422, 'Specialties status column is missing. Run migrations.');
        }
        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $specialty = Specialty::findOrFail($id);
        $specialty->status = $payload['status'] === 'active' ? 1 : 0;
        $specialty->save();

        return $this->returnJSON([
            'id' => $specialty->id,
            'status' => (int) $specialty->status,
        ], 'Specialty status updated', 'success');
    }

    public function deleteSpecialty($id)
    {
        $this->ensureAdminAuth();
        Specialty::findOrFail($id)->delete();
        return $this->returnJSON(['id' => $id], 'Specialty deleted successfully', 'success');
    }

    public function governorates(Request $request)
    {
        $this->ensureAdminAuth();
        $columns = ['id', 'name'];
        if (Schema::hasColumn('governorates', 'status')) $columns[] = 'status';
        $data = Governorate::orderBy('id')->get($columns);
        return $this->returnJSON($data, 'Governorates', 'success');
    }

    public function governorateDetails($id)
    {
        $this->ensureAdminAuth();
        $item = Governorate::findOrFail($id);
        return $this->returnJSON([
            'id' => $item->id,
            'name' => $item->name,
            'status' => (int) ($item->status ?? 1),
        ], 'Governorate details', 'success');
    }

    public function createGovernorate(Request $request)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name' => 'required|string|max:255|unique:governorates,name',
            'status' => 'nullable|in:active,inactive',
        ]);
        $item = Governorate::create([
            'name' => $payload['name'],
            ...(Schema::hasColumn('governorates', 'status') ? ['status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0] : []),
        ]);
        return $this->returnJSON($item, 'Governorate created successfully', 'success');
    }

    public function updateGovernorate(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name' => "required|string|max:255|unique:governorates,name,{$id}",
        ]);
        $item = Governorate::findOrFail($id);
        $item->update($payload);
        return $this->returnJSON(['id' => $item->id], 'Governorate updated successfully', 'success');
    }

    public function updateGovernorateStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();
        if (!Schema::hasColumn('governorates', 'status')) {
            abort(422, 'Governorates status column is missing. Run migrations.');
        }
        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);
        $item = Governorate::findOrFail($id);
        $item->status = $payload['status'] === 'active' ? 1 : 0;
        $item->save();
        return $this->returnJSON(['id' => $item->id, 'status' => (int) $item->status], 'Governorate status updated', 'success');
    }

    public function deleteGovernorate($id)
    {
        $this->ensureAdminAuth();
        Governorate::findOrFail($id)->delete();
        return $this->returnJSON(['id' => $id], 'Governorate deleted successfully', 'success');
    }

    public function cities(Request $request)
    {
        $this->ensureAdminAuth();
        $query = City::with('governorate:id,name');
        if ($request->filled('governorate_id')) {
            $query->where('governorate_id', $request->governorate_id);
        }
        $data = $query->orderBy('id')->get()->map(fn ($city) => [
            'id' => $city->id,
            'name' => $city->name,
            'governorate_id' => $city->governorate_id,
            'governorate_name' => $city->governorate->name ?? null,
            'status' => (int) ($city->status ?? 1),
        ]);
        return $this->returnJSON($data, 'Cities', 'success');
    }

    public function cityDetails($id)
    {
        $this->ensureAdminAuth();
        $city = City::with('governorate:id,name')->findOrFail($id);
        return $this->returnJSON([
            'id' => $city->id,
            'name' => $city->name,
            'governorate_id' => $city->governorate_id,
            'governorate_name' => $city->governorate->name ?? null,
            'status' => (int) ($city->status ?? 1),
        ], 'City details', 'success');
    }

    public function createCity(Request $request)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'governorate_id' => 'required|integer|exists:governorates,id',
            'status' => 'nullable|in:active,inactive',
        ]);
        $item = City::create([
            'name' => $payload['name'],
            'governorate_id' => $payload['governorate_id'],
            ...(Schema::hasColumn('cities', 'status') ? ['status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0] : []),
        ]);
        return $this->returnJSON($item, 'City created successfully', 'success');
    }

    public function updateCity(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'governorate_id' => 'required|integer|exists:governorates,id',
        ]);
        $item = City::findOrFail($id);
        $item->update($payload);
        return $this->returnJSON(['id' => $item->id], 'City updated successfully', 'success');
    }

    public function updateCityStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();
        if (!Schema::hasColumn('cities', 'status')) {
            abort(422, 'Cities status column is missing. Run migrations.');
        }
        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);
        $item = City::findOrFail($id);
        $item->status = $payload['status'] === 'active' ? 1 : 0;
        $item->save();
        return $this->returnJSON(['id' => $item->id, 'status' => (int) $item->status], 'City status updated', 'success');
    }

    public function deleteCity($id)
    {
        $this->ensureAdminAuth();
        City::findOrFail($id)->delete();
        return $this->returnJSON(['id' => $id], 'City deleted successfully', 'success');
    }

    public function areas(Request $request)
    {
        $this->ensureAdminAuth();
        $query = Area::with(['city:id,name', 'governorate:id,name']);
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        $data = $query->orderBy('id')->get()->map(fn ($area) => [
            'id' => $area->id,
            'name' => $area->name,
            'city_id' => $area->city_id,
            'city_name' => $area->city->name ?? null,
            'governorate_id' => $area->governorate_id,
            'governorate_name' => $area->governorate->name ?? null,
            'status' => (int) ($area->status ?? 1),
        ]);
        return $this->returnJSON($data, 'Areas', 'success');
    }

    public function areaDetails($id)
    {
        $this->ensureAdminAuth();
        $area = Area::with(['city:id,name', 'governorate:id,name'])->findOrFail($id);
        return $this->returnJSON([
            'id' => $area->id,
            'name' => $area->name,
            'city_id' => $area->city_id,
            'city_name' => $area->city->name ?? null,
            'governorate_id' => $area->governorate_id,
            'governorate_name' => $area->governorate->name ?? null,
            'status' => (int) ($area->status ?? 1),
        ], 'Area details', 'success');
    }

    public function createArea(Request $request)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'governorate_id' => 'required|integer|exists:governorates,id',
            'status' => 'nullable|in:active,inactive',
        ]);
        $item = Area::create([
            'name' => $payload['name'],
            'city_id' => $payload['city_id'],
            'governorate_id' => $payload['governorate_id'],
            ...(Schema::hasColumn('areas', 'status') ? ['status' => ($payload['status'] ?? 'active') === 'active' ? 1 : 0] : []),
        ]);
        return $this->returnJSON($item, 'Area created successfully', 'success');
    }

    public function updateArea(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'governorate_id' => 'required|integer|exists:governorates,id',
        ]);
        $item = Area::findOrFail($id);
        $item->update($payload);
        return $this->returnJSON(['id' => $item->id], 'Area updated successfully', 'success');
    }

    public function updateAreaStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();
        if (!Schema::hasColumn('areas', 'status')) {
            abort(422, 'Areas status column is missing. Run migrations.');
        }
        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);
        $item = Area::findOrFail($id);
        $item->status = $payload['status'] === 'active' ? 1 : 0;
        $item->save();
        return $this->returnJSON(['id' => $item->id, 'status' => (int) $item->status], 'Area status updated', 'success');
    }

    public function deleteArea($id)
    {
        $this->ensureAdminAuth();
        Area::findOrFail($id)->delete();
        return $this->returnJSON(['id' => $id], 'Area deleted successfully', 'success');
    }

    public function users(Request $request)
    {
        $this->ensureAdminAuth();
        $query = User::with('roles')->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'roles' => $u->roles->pluck('name')->toArray(),
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Users', 'success');
    }

    public function roles(Request $request)
    {
        $this->ensureAdminAuth();
        $roles = Role::where('guard_name', 'admin')
            ->withCount('permissions')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'permissions_count' => $r->permissions_count]);
        return $this->returnJSON($roles, 'Roles', 'success');
    }

    public function reviews(Request $request)
    {
        $this->ensureAdminAuth();
        $query = PatientReview::with('patient:id,name')->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($r) => [
            'id' => $r->id,
            'patient_name' => $r->patient->name ?? 'N/A',
            'rating' => $r->rating ?? null,
            'comment' => $r->comment ?? null,
            'is_active' => (bool) ($r->is_active ?? true),
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Reviews', 'success');
    }

    public function reviewDetails($id)
    {
        $this->ensureAdminAuth();
        $review = PatientReview::with('patient:id,name')->findOrFail($id);
        return $this->returnJSON([
            'id' => $review->id,
            'patient_id' => $review->patient_id,
            'patient_name' => $review->patient->name ?? null,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'is_active' => (bool) ($review->is_active ?? true),
        ], 'Review details', 'success');
    }

    public function createReview(Request $request)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);
        $review = PatientReview::create([
            'patient_id' => $payload['patient_id'],
            'rating' => $payload['rating'],
            'comment' => $payload['comment'],
            'is_active' => $payload['is_active'] ?? true,
        ]);
        return $this->returnJSON(['id' => $review->id], 'Review created successfully', 'success');
    }

    public function updateReview(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);
        $review = PatientReview::findOrFail($id);
        $review->update($payload);
        return $this->returnJSON(['id' => $review->id], 'Review updated successfully', 'success');
    }

    public function updateReviewStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);
        $review = PatientReview::findOrFail($id);
        $review->is_active = $payload['status'] === 'active';
        $review->save();
        return $this->returnJSON(['id' => $review->id, 'is_active' => (bool) $review->is_active], 'Review status updated', 'success');
    }

    public function deleteReview($id)
    {
        $this->ensureAdminAuth();
        PatientReview::findOrFail($id)->delete();
        return $this->returnJSON(['id' => $id], 'Review deleted successfully', 'success');
    }

    public function announcements(Request $request)
    {
        $this->ensureAdminAuth();
        $query = Announcement::query()->orderBy('id', 'desc');
        $perPage = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title ?? null,
            'body' => $a->body ?? null,
            'is_active' => (bool) ($a->is_active ?? true),
        ]);
        return $this->returnJSON([
            'data' => $data,
            'pagination' => $this->pagination($paginated),
        ], 'Announcements', 'success');
    }

    public function announcementDetails($id)
    {
        $this->ensureAdminAuth();
        $item = Announcement::findOrFail($id);
        return $this->returnJSON([
            'id' => $item->id,
            'title' => $item->title,
            'body' => $item->body,
            'type' => $item->type,
            'is_active' => (bool) ($item->is_active ?? true),
            'start_date' => optional($item->start_date)?->toDateTimeString(),
            'end_date' => optional($item->end_date)?->toDateTimeString(),
        ], 'Announcement details', 'success');
    }

    public function createAnnouncement(Request $request)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|in:text,banner',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        $item = Announcement::create([
            'title' => $payload['title'],
            'body' => $payload['body'],
            'type' => $payload['type'] ?? 'text',
            'is_active' => $payload['is_active'] ?? true,
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
        ]);
        return $this->returnJSON(['id' => $item->id], 'Announcement created successfully', 'success');
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|in:text,banner',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        $item = Announcement::findOrFail($id);
        $item->update($payload);
        return $this->returnJSON(['id' => $item->id], 'Announcement updated successfully', 'success');
    }

    public function updateAnnouncementStatus(Request $request, $id)
    {
        $this->ensureAdminAuth();
        $payload = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);
        $item = Announcement::findOrFail($id);
        $item->is_active = $payload['status'] === 'active';
        $item->save();
        return $this->returnJSON(['id' => $item->id, 'is_active' => (bool) $item->is_active], 'Announcement status updated', 'success');
    }

    public function deleteAnnouncement($id)
    {
        $this->ensureAdminAuth();
        Announcement::findOrFail($id)->delete();
        return $this->returnJSON(['id' => $id], 'Announcement deleted successfully', 'success');
    }

    private function ensureAdminAuth(): void
    {
        if (!Auth::guard('admin_api')->check()) {
            abort(401, 'Unauthenticated');
        }
    }

    private function pagination($paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ];
    }
}
