@extends('backend.dashboards.medicalLaboratory.layouts.master')
@section('css')
    <link href="{{ URL::asset('backend/assets/css/datatables.min.css') }}" rel="stylesheet">
@endsection
@section('title')
    {{ __('Reviews') }}
@endsection

@section('page-header')
<h4 class="page-title">{{__('Reviews')}}</h4>

@endsection

@section('content')
   

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
                ajax: "{{ route('medicalLaboratory.reviews.data') }}",
                columns: [
                   
                    {
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
