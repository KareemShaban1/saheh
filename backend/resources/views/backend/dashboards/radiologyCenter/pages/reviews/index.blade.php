@extends('backend.dashboards.radiologyCenter.layouts.master')
@section('css')
<link href="{{ URL::asset('backend/assets/css/datatables.min.css') }}" rel="stylesheet">
@endsection
@section('title')
{{ __('Reviews') }}
@endsection
@section('content')
<div class="page-title">
    <div class="row">
        <div class="col-sm-6">
            <h4 class="mb-0">{{ __('Reviews') }}</h4>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right ">
                <li class="breadcrumb-item"><a href="{{ route('radiologyCenter.dashboard') }}"
                        class="default-color">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Reviews') }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reviews-table" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{ __('Patient') }}</th>
                                <th>{{ __('Rating') }}</th>
                                <th>{{ __('Comment') }}</th>
                                <th>{{ __('Created At') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('backend/assets/js/datatables.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#reviews-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('radiologyCenter.reviews.data') }}",
            columns: [{
                    data: 'patient_name',
                    name: 'patient_name'
                },
                {
                    data: 'rating',
                    name: 'rating'
                },
                {
                    data: 'comment',
                    name: 'comment'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                }
            ]
        });
    });
</script>
@endpush