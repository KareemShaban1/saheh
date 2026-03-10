@extends('backend.dashboards.medicalLaboratory.layouts.master')
@section('css')

@section('title')
{{ trans('backend/settings_trans.Settings') }}
@stop

@endsection

@section('page-header')
<h4 class="page-title">{{ trans('backend/settings_trans.Settings') }}</h4>
@endsection
@section('content')

<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <x-backend.alert />



                <form method="post" enctype="multipart/form-data"
                    action="{{ Route('medicalLaboratory.settings.medicalLaboratorySettings.update') }}" autocomplete="off">
                    @csrf
                    {{-- @method('PUT') --}}
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-4 col-sm-6 col-5">
                            <h5 class="setting-title">{{ trans('backend/settings_trans.Medical_Laboratory_Name') }}<span
                                    class="text-danger">*</span></h5>
                        </div>
                        <div class="col-lg-6 col-md-8 col-sm-6 col-7"><input type="text" name="medical_laboratory_name"
                                value="{{ $settings['medical_laboratory_name'] ?? '' }}" class="form-control"></div>
                    </div>



                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-4 col-sm-6 col-5">
                            <h5> {{ trans('backend/settings_trans.Medical_Laboratory_Address') }} <span class="text-danger">*</span>
                            </h5>
                        </div>
                        <div class="col-lg-6 col-md-8 col-sm-6 col-7">
                            <input class="form-control" name="medical_laboratory_address" value="{{ $settings['medical_laboratory_address'] ?? '' }}"
                                type="text">
                        </div>


                    </div>

                   
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-4 col-sm-6 col-5">
                            <h5> {{ trans('backend/settings_trans.Phone') }} <span class="text-danger">*</span></h5>
                        </div>
                        <div class="col-lg-6 col-md-8 col-sm-6 col-7">
                            <input class="form-control" name="phone" value="{{ $settings['phone'] ?? '' }}" type="text">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-4 col-sm-6 col-5">
                            <h5> {{ trans('backend/settings_trans.Email') }} <span class="text-danger">*</span></h5>
                        </div>
                        <div class="col-lg-6 col-md-8 col-sm-6 col-7">
                            <input class="form-control" name="email" value="{{ $settings['email'] ?? '' }}" type="text">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-4 col-sm-6 col-5">
                            <h5> {{ trans('backend/settings_trans.Website') }} <span class="text-danger">*</span></h5>
                        </div>
                        <div class="col-lg-6 col-md-8 col-sm-6 col-7">
                            <input class="form-control" name="website" value="{{ $settings['website'] ?? '' }}"
                                type="text">
                        </div>
                    </div>


                    <button type="submit"
                        class="btn btn-success btn-md nextBtn btn-lg ">{{ trans('backend/settings_trans.Save') }}</button>


                </form>


            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
@section('js')

@endsection