@extends('backend.dashboards.admin.layouts.master')

@section('title')
{{__('Pending Clinics Registrations')}}
@endsection

@section('page-header')
<h4 class="page-title">{{__('Pending Clinics Registrations')}}</h4>
@endsection

@section('content')
<div class="mt-4">

    @forelse ($pendingRegistrations as $item)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ $item['clinic']['name'] ?? 'Unknown Clinic' }}</strong>
            <div>
                <a href="{{ route('admin.clinic-temp-data.approveClinic', $item['batch_id']) }}" class="btn btn-success btn-sm">Approve</a>
                <form method="POST" action="{{ route('admin.clinic-temp-data.destroyClinic', $item['batch_id']) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this temp registration?')">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body" style="direction: rtl;">
            <p><strong>Name:</strong> {{ $item['clinic']['name'] ?? 'N/A' }}</p>
            <p><strong>specialty:</strong> {{ App\Models\Specialty::where('id',$item['clinic']['specialty_id'])->first()->name_ar ?? 'N/A' }}</p>
            <p><strong>Governorate:</strong> {{ App\Models\Governorate::where('id',$item['clinic']['governorate_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>City:</strong> {{ App\Models\City::where('id',$item['clinic']['city_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>Area:</strong> {{ App\Models\Area::where('id',$item['clinic']['area_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>Start Date:</strong> {{ $item['clinic']['start_date'] ?? 'N/A' }}</p>
            <p><strong>Address:</strong> {{ $item['clinic']['address'] ?? 'N/A' }}</p>
            <p><strong>Email:</strong> {{ $item['clinic']['email'] ?? 'N/A' }}</p>
            <p><strong>Phone:</strong> {{ $item['clinic']['phone'] ?? 'N/A' }}</p>
            <p><strong>User:</strong> {{ $item['user']['name'] ?? '' }} ({{ $item['user']['email'] ?? '' }})</p>
        </div>
    </div>
    @empty
    <p>No pending clinics found.</p>
    @endforelse
</div>
@endsection
