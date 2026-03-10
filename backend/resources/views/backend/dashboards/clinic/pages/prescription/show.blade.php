@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{ trans('backend/reservations_trans.Show_Reservation') }}
@stop
@endsection
@section('page-header')
<h4 class="page-title">{{ trans('backend/drugs_trans.Prescription') }}</h4>

@endsection
@section('content')
<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <div class="content-body">
                    <div class="container-fluid">

                        <div class="row">



                            <div class="col-xl-12 col-xxl-8 col-lg-8">

                                <div class="card">
                                    <div class="card-body">

                                        <div class="profile-tab">

                                            <div class="custom-tab-1">
                                                <ul class="nav nav-tabs">
                                                    <li class="nav-item">
                                                        <a href="#info" data-toggle="tab"
                                                            class="nav-link active show">
                                                            {{ trans('backend/reservations_trans.Reservation_Information') }}
                                                        </a>
                                                    </li>

                                                    <li class="nav-item">
                                                        <a href="#prescription" data-toggle="tab" class="nav-link">
                                                            {{ trans('backend/reservations_trans.Prescription') }}
                                                        </a>
                                                    </li>

                                                </ul>

                                                <div class="tab-content">

                                                    <div id="info" class="tab-pane fade active show">


                                                        <div class="my-post-content pt-4">


                                                            <h5 class="card-header">
                                                                <span class="badge badge-rounded badge-warning ">
                                                                    <h5> {{ trans('backend/reservations_trans.Reservation_Information') }}
                                                                    </h5>
                                                                </span>
                                                            </h5>

                                                            <div class="card-body">

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Id') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $reservation->id }}</span>
                                                                    </div>
                                                                </div>


                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Number_of_Reservation') }}<span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $reservation->reservation_number }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.First_Diagnosis') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $reservation->first_diagnosis }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Reservation_Type') }}<span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6"><span>
                                                                            <p>
                                                                                @if ($reservation->type == 'check')
                                                                                {{ trans('backend/reservations_trans.Check') }}
                                                                                @elseif($reservation->type == 'recheck')
                                                                                {{ trans('backend/reservations_trans.Recheck') }}
                                                                                @elseif($reservation->type == 'consultation')
                                                                                {{ trans('backend/reservations_trans.Consultation') }}
                                                                                @endif
                                                                            </p>
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Reservation_Status') }}<span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6"><span>
                                                                            <p>
                                                                                @if ($reservation->status == 'waiting')
                                                                                <span
                                                                                    class="badge badge-rounded badge-warning text-white p-2 mb-2">
                                                                                    {{ trans('backend/reservations_trans.Waiting') }}
                                                                                </span>
                                                                                @elseif($reservation->status == 'entered')
                                                                                <span
                                                                                    class="badge badge-rounded badge-success p-2 mb-2">
                                                                                    {{ trans('backend/reservations_trans.Entered') }}
                                                                                </span>
                                                                                @elseif($reservation->status == 'finished')
                                                                                <span
                                                                                    class="badge badge-rounded badge-danger p-2 mb-2">
                                                                                    {{ trans('backend/reservations_trans.Finished') }}
                                                                                </span>
                                                                                @endif
                                                                            </p>
                                                                        </span>
                                                                    </div>
                                                                </div>


                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Payment') }}<span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6"><span>
                                                                            <p>
                                                                                @if ($reservation->payment == 'paid')
                                                                                <span
                                                                                    class="badge badge-rounded badge-success">{{ trans('backend/reservations_trans.Paid') }}</span>
                                                                                @elseif($reservation->payment == 'not_paid')
                                                                                <span
                                                                                    class="badge badge-rounded badge-danger">{{ trans('backend/reservations_trans.Not_Paid') }}</span>
                                                                                @endif
                                                                            </p>
                                                                        </span>
                                                                    </div>
                                                                </div>


                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Reservation_Date') }}<span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $reservation->date }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/reservations_trans.Final_Diagnosis') }}<span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $reservation->final_diagnosis }}</span>
                                                                    </div>
                                                                </div>



                                                            </div>


                                                        </div>


                                                    </div>




                                                    <div id="prescription" class="tab-pane fade">

                                                        <div class="my-post-content pt-4">


                                                            @forelse($drugs as $drug)
                                                            <h5 class="card-header">
                                                                <span class="badge badge-rounded badge-warning ">
                                                                    <h5> {{ trans('backend/reservations_trans.Prescription_Number') }}
                                                                        {{ $loop->index + 1 }}
                                                                    </h5>
                                                                </span>
                                                            </h5>
                                                            <div class="card-body">

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Id') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->id }}</span>
                                                                    </div>
                                                                </div>


                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Drug_Name') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->name }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Type') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->type }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Drug_Dose') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->dose }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Frequency') }}
                                                                            <span
                                                                                class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->frequency }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Quantity') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->quantity }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Period') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->period }}</span>
                                                                    </div>
                                                                </div>

                                                                <div class="row mb-4">
                                                                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                                                        <h5 class="f-w-500">
                                                                            {{ trans('backend/drugs_trans.Notes') }}
                                                                            <span class="pull-left">:</span>
                                                                        </h5>
                                                                    </div>
                                                                    <div class="col-lg-9 col-md-8 col-sm-6 col-6">
                                                                        <span>{{ $drug->notes }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @empty

                                                            <div>لا يوجد روشتة لهذا الكشف</div>
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