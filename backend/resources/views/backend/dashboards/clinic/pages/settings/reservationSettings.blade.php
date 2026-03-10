@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{ trans('backend/reservation_settings_trans.Reservation_Settings') }}
@stop
@endsection

@section('page-header')

<h4 class="page-title"> {{ trans('backend/reservation_settings_trans.Reservation_Settings') }}</h4>

@endsection

@section('content')
<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <x-backend.alert />

                <form method="post" enctype="multipart/form-data"
                    action="{{ Route('clinic.settings.reservationSettings.update') }}" autocomplete="off">

                    @csrf

                    @php
                    $settingsFields = [


                    [
                    'name' => 'reservation_settings',
                    'label' => 'Reservation_Settings',
                    'type' => 'select',
                    'options' => [
                    'number' => trans('backend/reservation_settings_trans.Number'),
                    'slots' => trans('backend/reservation_settings_trans.Slots'),
                    ]
                    ],

                    [
                    'name' => 'reservation_numbers_default',
                    'label' => 'reservation_numbers_default',
                    'type' => 'text'
                    ],

                    [
                    'name' => 'reservation_slots_duration',
                    'label' => 'Reservation_Slots_Duration',
                    'type' => 'text'
                    ],
                    [
                    'name'=>'reservation_slots_start_time',
                    'label'=> 'Reservation_Slots_Start_Time',
                    'type'=>'time'
                    ],
                    [
                    'name'=>'reservation_slots_end_time',
                    'label'=> 'Reservation_Slots_End_Time',
                    'type'=>'time'
                    ],

                    ];
                    @endphp

                    @foreach ($settingsFields as $field)
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-4 col-sm-6 col-7">
                            <h5 class="setting-title">
                                {{ trans("backend/reservation_settings_trans.{$field['label']}") }}
                                <span class="text-danger">*</span>
                            </h5>
                        </div>
                        <div class="col-lg-3 col-md-8 col-sm-6 col-5 mb-2">
                            @if($field['type'] === 'select')
                            <select class="custom-select" name="{{ $field['name'] }}">
                                <option disabled {{ !isset($settings[$field['name']]) ? 'selected' : '' }}>
                                    {{ trans('backend/reservation_settings_trans.Choose') }}
                                </option>
                                @foreach($field['options'] as $value => $label)
                                <option value="{{ $value }}" {{ isset($settings[$field['name']]) && $settings[$field['name']] == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            @elseif($field['type'] === 'radio')
                            @foreach($field['options'] as $value => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="{{ $field['name'] }}" id="{{ $field['name'] }}_{{ $value }}"
                                    value="{{ $value }}" {{ isset($settings[$field['name']]) && $settings[$field['name']] == $value ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $field['name'] }}_{{ $value }}">{{ $label }}</label>
                            </div>
                            @endforeach
                            @elseif(in_array($field['type'], ['text', 'time', 'number']))
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ $settings[$field['name']] ?? '' }}" class="form-control">
                            @endif
                        </div>
                    </div>
                    @endforeach




                    <button type="submit"
                        class="btn btn-success btn-md nextBtn btn-lg ">{{ trans('backend/reservation_settings_trans.Save') }}</button>


                </form>


            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
@section('js')

@endsection