@extends('backend.dashboards.admin.layouts.master')

@section('title')
{{__('Pending Medical Laboratories Registrations')}}
@endsection


@section('page-header')
<h4 class="page-title">{{__('Pending Medical Laboratories Registrations')}}</h4>

@endsection

@section('content')
<div class="mt-4">

    @forelse ($pendingRegistrations as $item)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ $item['medicalLaboratory']['name'] ?? 'Unknown Medical Laboratory' }}</strong>
            <div>
                <a href="{{ route('admin.medical-laboratory-temp-data.approveMedicalLaboratory', $item['batch_id']) }}" class="btn btn-success btn-sm">Approve</a>
                <form method="POST" action="{{ route('admin.medical-laboratory-temp-data.destroyMedicalLaboratory', $item['batch_id']) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this temp registration?')">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> {{ $item['medicalLaboratory']['name'] ?? 'N/A' }}</p>
            <p><strong>Governorate:</strong> {{ App\Models\Governorate::where('id',$item['medicalLaboratory']['governorate_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>City:</strong> {{ App\Models\City::where('id',$item['medicalLaboratory']['city_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>Area:</strong> {{ App\Models\Area::where('id',$item['medicalLaboratory']['area_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>Start Date:</strong> {{ $item['medicalLaboratory']['start_date'] ?? 'N/A' }}</p>
            <p><strong>Address:</strong> {{ $item['medicalLaboratory']['address'] ?? 'N/A' }}</p>
            <p><strong>Email:</strong> {{ $item['medicalLaboratory']['email'] ?? 'N/A' }}</p>
            <p><strong>Phone:</strong> {{ $item['medicalLaboratory']['phone'] ?? 'N/A' }}</p>
            <p><strong>User:</strong> {{ $item['user']['name'] ?? '' }} ({{ $item['user']['email'] ?? '' }})</p>
        </div>
    </div>
    @empty
    <p>No pending Medical Laboratories found.</p>
    @endforelse
</div>
@endsection