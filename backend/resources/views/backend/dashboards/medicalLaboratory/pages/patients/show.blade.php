@extends('backend.dashboards.medicalLaboratory.layouts.master')
@section('css')

@section('title')
{{ trans('backend/patients_trans.Patients') }}
@stop
@endsection
@section('page-header')

<h4 class="page-title">{{ trans('backend/patients_trans.Patient_Profile') }}</h4>

@endsection
@section('content')
<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <div class="content-body">
                    <!-- row -->
                    <div class="container-fluid p-0">

                        <div class="row">

                            <div class="col-xl-12 col-xxl-8 col-lg-8 p-0">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="profile-tab">
                                            <div class="custom-tab-1">
                                                <ul class="nav nav-tabs">
                                                    <li class="nav-item">
                                                        <a href="#info" data-toggle="tab"
                                                            class="nav-link active show">{{ trans('backend/patients_trans.Patient_Information') }}</a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#analysis" data-toggle="tab"
                                                            class="nav-link">{{ trans('backend/patients_trans.Analysis') }}</a>
                                                    </li>
                                                </ul>

                                                <div class="tab-content">

                                                    <div id="info" class="tab-pane fade active show">


                                                        <div class="profile-personal-info pt-4">


                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Patient_Name') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->name }}</span>
                                                                </div>
                                                            </div>


                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Email') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->email }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Age') }} <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->age }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Phone') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->phone }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Address') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->address }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Medical_Analysis_Count') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->medicalAnalysis->count() }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Patient_Gender') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>
                                                                        @if ($patient->gender == 'male')
                                                                        {{ trans('backend/patients_trans.Male') }}
                                                                        @elseif ($patient->gender == 'female')
                                                                        {{ trans('backend/patients_trans.Female') }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </div>


                                                            <div class="row mb-4">
                                                                <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                    <h5 class="f-w-500">
                                                                        {{ trans('backend/patients_trans.Blood_Group') }}
                                                                        <span
                                                                            class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                    </h5>
                                                                </div>
                                                                <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                    <span>{{ $patient->blood_group }}</span>
                                                                </div>
                                                            </div>


                                                        </div>

                                                    </div>


                                                    <div id="analysis" class="tab-pane fade">

                                                        <div class="my-post-content pt-4">


                                                            @forelse($patient->medicalAnalysis as $analysis)
                                                            <h5 class="card-header">
                                                                <span class="badge badge-rounded badge-warning ">
                                                                    <h5> {{ trans('backend/patients_trans.Analysis_Number') }}
                                                                        {{ $loop->index + 1 }}
                                                                    </h5>
                                                                </span>
                                                            </h5>
                                                            <div class="card-body">

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/patients_trans.Id') }}
                                                                            <span
                                                                                class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $analysis->id }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/patients_trans.Date') }}
                                                                            <span
                                                                                class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $analysis->date }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/patients_trans.Cost') }}
                                                                            <span
                                                                                class="{{ trans('backend/patients_trans.pull') }}">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $analysis->cost }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/patients_trans.Payment') }}
                                                                            <span
                                                                                class="{{ trans('backend/patients_trans.pull') }}:"></span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $analysis->payment === 'paid' ? 'تم الدفع' : 'لم يتم الدفع' }}</span>
                                                                    </div>
                                                                </div>


                                                                @isset($analysis->labServiceOptions)
                                                                @foreach ($analysis->labServiceOptions as $labServiceOption)

                                                             <div class="card">
                                                                <div class="card-header"></div>
                                                                <div class="card-body">
                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/patients_trans.Medical_Analysis') }}
                                                                            {{ $loop->index + 1 }}
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">

                                                                        <div class="row">
                                                                            <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                                <h5 class="f-w-500">
                                                                                    {{ trans('backend/patients_trans.Name') }}
                                                                                </h5>
                                                                            </div>
                                                                            <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                                <span>{{ $labServiceOption->name }}</span>
                                                                            </div>
                                                                        </div>



                                                                        <br>

                                                                        <div class="row">
                                                                            <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                                <h5 class="f-w-500">
                                                                                    {{ trans('backend/patients_trans.Price') }}
                                                                                </h5>
                                                                            </div>
                                                                            <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                                <span>{{ $labServiceOption->price }}</span>
                                                                            </div>
                                                                        </div>

                                                                        
                                                                        <br>
                                                                        <div class="row">
                                                                            <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                                <h5 class="f-w-500">
                                                                                    {{ trans('backend/patients_trans.Notes') }}
                                                                                </h5>
                                                                            </div>
                                                                            <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                                <span>{{ $labServiceOption->notes }}</span>
                                                                            </div>
                                                                        </div>



                                                                    </div>

                                                                </div>
                                                                    
                                                                </div>
                                                             </div>
                                                                @endforeach
                                                                @endisset

                                                            </div>
                                                            @empty
                                                            <div> لا توجد تحاليل للمريض </div>
                                                            @endforelse
                                                        </div>


                                                    </div>





                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
@section('js')

@endsection