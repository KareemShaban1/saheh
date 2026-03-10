@extends('backend.dashboards.clinic.layouts.master')


@section('title')
    {{ trans('backend/patient_reviews.Reviews') }}
@endsection


@section('page-header')
    <h4 class="page-title">{{ trans('backend/patient_reviews.Reviews') }}</h4>
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
                                    <th>{{ trans('backend/patient_reviews.Doctor') }}</th>
                                    <th>{{ trans('backend/patient_reviews.Patient') }}</th>
                                    <th>{{ trans('backend/patient_reviews.Rating') }}</th>
                                    <th>{{ trans('backend/patient_reviews.Comment') }}</th>
                                    <th>{{ trans('backend/patient_reviews.Created_At') }}</th>
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
    <script>
        $(document).ready(function() {
            $('#reviews-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('clinic.reviews.data') }}",
                columns: [{
                        data: 'doctor_name',
                        name: 'doctor_name'
                    },
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
