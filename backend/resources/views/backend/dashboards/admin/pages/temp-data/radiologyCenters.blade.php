@extends('backend.dashboards.admin.layouts.master')

@section('title')
{{__('Pending Radiology Centers Registrations')}}
@endsection

@section('page-header')
<h4 class="page-title">{{__('Pending Radiology Centers Registrations')}}</h4>

@endsection

@section('content')
<div class="mt-4">

    @forelse ($pendingRegistrations as $item)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ $item['radiologyCenter']['name'] ?? 'Unknown Radiology Center' }}</strong>
            <div>
                <a href="{{ route('admin.radiology-center-temp-data.approveRadiologyCenter', $item['batch_id']) }}" class="btn btn-success btn-sm">Approve</a>
                <form method="POST" action="{{ route('admin.radiology-center-temp-data.destroyRadiologyCenter', $item['batch_id']) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this temp registration?')">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> {{ $item['radiologyCenter']['name'] ?? 'N/A' }}</p>
            <p><strong>Governorate:</strong> {{ App\Models\Governorate::where('id',$item['radiologyCenter']['governorate_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>City:</strong> {{ App\Models\City::where('id',$item['radiologyCenter']['city_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>Area:</strong> {{ App\Models\Area::where('id',$item['radiologyCenter']['area_id'])->first()->name ?? 'N/A' }}</p>
            <p><strong>Start Date:</strong> {{ $item['radiologyCenter']['start_date'] ?? 'N/A' }}</p>
            <p><strong>Address:</strong> {{ $item['radiologyCenter']['address'] ?? 'N/A' }}</p>
            <p><strong>Email:</strong> {{ $item['radiologyCenter']['email'] ?? 'N/A' }}</p>
            <p><strong>Phone:</strong> {{ $item['radiologyCenter']['phone'] ?? 'N/A' }}</p>
            <p><strong>User:</strong> {{ $item['user']['name'] ?? '' }} ({{ $item['user']['email'] ?? '' }})</p>
        </div>
    </div>
    @empty
    <p>No pending radiology centers found.</p>
    @endforelse
</div>
@endsection