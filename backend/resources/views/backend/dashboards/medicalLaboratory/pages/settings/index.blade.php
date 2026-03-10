@extends('backend.dashboards.medicalLaboratory.layouts.master')
@section('css')

@section('title')
    {{ trans('backend/settings_trans.Settings') }}
@stop

@endsection
@section('page-header')

<h4 class="page-title"> {{ trans('backend/settings_trans.Settings') }}</h4>

@endsection
@section('content')

<div class="row">
    <div class="col-md-3">
        <div class="settings-container">
            <a href="{{ Route('medicalLaboratory.settings.medicalLaboratorySettings.index') }}">
                {{ trans('backend/settings_trans.Medical Settings') }}
            </a>
        </div>
    </div>

    



</div>
@endsection
