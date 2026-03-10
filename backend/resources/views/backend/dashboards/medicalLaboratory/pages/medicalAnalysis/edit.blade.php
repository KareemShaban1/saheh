@extends('backend.dashboards.medicalLaboratory.layouts.master')
@section('css')
@section('title')
{{ trans('backend/medicalAnalysis_trans.Edit_Analysis') }}
@stop
@endsection

@section('page-header')
<h4 class="page-title"> {{ trans('backend/medicalAnalysis_trans.Edit_Analysis') }}</h4>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <x-backend.alert />

                <form method="POST" enctype="multipart/form-data" action="{{ route('medicalLaboratory.analysis.update', $analysis->id) }}" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>{{ trans('backend/medicalAnalysis_trans.Patient_Name') }}</label>
                                <select name="patient_id" id="patient_id" class="custom-select mr-sm-2">
                                    <option value="{{ $analysis->patient->id }}" selected>{{ $analysis->patient->name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>{{ trans('backend/medicalAnalysis_trans.Analysis_Date') }} <span class="text-danger">*</span></label>
                                <input class="form-control" name="date" id="datepicker-action" data-date-format="yyyy-mm-dd" value="{{ $analysis->date }}">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label for="payment">{{ trans('backend/medicalAnalysis_trans.Payment') }}<span
                                        class="text-danger">*</span></label>
                                <select name="payment" id="payment" class="custom-select mr-sm-2">
                                    <option value="">{{ trans('backend/medicalAnalysis_trans.Select_Payment') }}</option>
                                    <option value="paid" {{ $analysis->payment == 'paid' ? 'selected' : '' }}>{{ trans('backend/medicalAnalysis_trans.Paid') }}</option>
                                    <option value="not_paid" {{ $analysis->payment == 'not_paid' ? 'selected' : '' }}>{{ trans('backend/medicalAnalysis_trans.Not_Paid') }}</option>
                                </select>

                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>{{ trans('backend/medicalAnalysis_trans.Doctor_Name') }} <span class="text-danger">*</span></label>
                                <input class="form-control" name="doctor_name" type="text" value="{{ $analysis->doctor_name }}">
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>{{ trans('backend/medicalAnalysis_trans.Cost') }} <span class="text-danger">*</span></label>
                                <input class="form-control" name="cost" type="text" value="{{ $analysis->cost }}">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label>{{ __('Notes') }}</label>
                            <textarea name="report" class="form-control">{{ $analysis->report }}</textarea>
                        </div>
                    </div>
                    <div id="service-fee-container">
                        <button type="button" class="btn btn-primary mb-3" id="add-service-fee">
                            {{ __('Add Service Fee') }}
                        </button>

                        @php
                        $groupedOptions = $analysis->labServiceOptions
                        ->load('labService.category')
                        ->groupBy(function ($item) {
                        return $item->labService->category->id ?? 'Uncategorized';
                        });

                        $groupIndex = 0;
                        @endphp

                        @foreach ($groupedOptions as $categoryId => $options)


                        <div class="service-fee-row" data-id="{{ $categoryId }}">
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label>{{ __('Service Name') }}</label>
                                    <select name="lab_service_category_id[]" class="service-fee-select form-control p-0">
                                        <option value="">{{ __('Select Service') }}</option>

                                        @foreach (App\Models\LabServiceCategory::all() as $serviceCategory)

                                        <option value="{{ $serviceCategory->id }}" {{ $serviceCategory->id == $categoryId ? 'selected' : '' }}>
                                            {{ $serviceCategory->category_name }}
                                        </option>

                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger remove-service-fee mt-2">{{ __('Remove') }}</button>
                                </div>
                            </div>


                            @foreach ($options as $i => $option)
                            <div class="service-options-container mb-3 px-3">

                                <div class="d-flex mb-2 service-options-row" data-id="{{ $option->id }}">
                                    <input type="hidden" name="lab_service_id[]" value="{{ $option->lab_service_id }}">
                                    <input type="text" name="name[]" class="form-control mr-2" placeholder="Name" value="{{ $option->name }}">
                                    <input type="text" name="price[]" class="form-control mr-2" placeholder="Price" value="{{ $option->price }}">
                                    <input type="text" name="value[]" class="form-control mr-2" placeholder="Value" value="{{ ($option->value === 'undefined' || $option->value === null) ? '' : $option->value }}">
                                    <input type="text" name="unit[]" class="form-control mr-2" placeholder="Unit" value="{{ $option->unit }}">
                                    <input type="text" name="normal_range[]" class="form-control mr-2" placeholder="Normal Range" value="{{ $option->normal_range }}">
                                    <button type="button" class="btn btn-danger remove-service-options">×</button>
                                </div>


                            </div>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-outline-primary add-service-options">
                                {{ __('Add Medical Analysis Option') }}
                            </button>
                        </div>

                        @php $groupIndex++; @endphp
                        @endforeach


                    </div>
                    <button type="submit" class="btn btn-success btn-md btn-lg">{{ trans('backend/medicalAnalysis_trans.Update') }}</button>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).on('click', '.remove-service-fee', function() {
        const $row = $(this).closest('.service-fee-row');
        const ServiceId = $row.data('id'); // assuming you store the DB id in data-id

        if (confirm('Are you sure you want to remove this service fee?')) {
            if (ServiceId) {
                // Send AJAX request to remove from the server
                $.ajax({
                    url: `medical-laboratory/service_fee/delete/${ServiceId}`, // update with actual route
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        $row.remove(); // Remove from DOM only after successful delete
                    },
                    error: function() {
                        alert('Failed to delete service fee.');
                    }
                });
            } else {
                // No ID, just remove from DOM
                $row.remove();
            }
        }
    });

    $(document).on('change', '.service-fee-select', function() {
        let $parentRow = $(this).closest('.service-fee-row');
        let ServiceId = $(this).val();
        let index = $parentRow.index();

        // Clear previous options
        $parentRow.find('.service-options-row').remove();

        if (!ServiceId) {
            return; // Exit if no service selected
        }

        $.ajax({
            url: `/medical-laboratory/serviceOptions/get-options-by-service/${ServiceId}`,
            method: 'GET',
            success: function(response) {
                response.forEach(function(option) {
                    const optionRow = `
                    <div class="d-flex mb-2 service-options-row" data-id="${option.id}">
                        <select class="form-control mr-2 option-selector">
                            <option value="${option.id}" selected
                                data-name="${option.name}"
                                data-price="${option.price}"
                                data-value="${option.value}"
                                data-unit="${option.unit}"
                                data-range="${option.normal_range}">
                                ${option.name}
                            </option>
                        </select>
                        <input type="hidden" name="lab_service_id[]" value="${option.id}">
                        <input type="text" name="name[]" class="form-control mr-2" placeholder="Name" value="${option.name}" readonly>
                        <input type="text" name="price[]" class="form-control mr-2" placeholder="Price" value="${option.price}" readonly>
                        <input type="text" name="value[]" class="form-control mr-2" placeholder="Value" value="${option.value}">
                        <input type="text" name="unit[]" class="form-control mr-2" placeholder="Unit" value="${option.unit}" readonly>
                        <input type="text" name="normal_range[]" class="form-control mr-2" placeholder="Normal Range" value="${option.normal_range}" readonly>
                        <button type="button" class="btn btn-danger remove-service-options">×</button>
                    </div>
                `;
                    $parentRow.find('.add-service-options').before(optionRow);
                });
            },
            error: function() {
                alert('Failed to fetch service options.');
            }
        });
    });


    $(document).on('click', '#add-service-fee', function() {
        const total = $('.service-fee-row').length;

        const newRow = $(`
        <div class="service-fee-row" data-index="${total}">
            <div class="row mb-3 align-items-center">
                <div class="col-md-3">
                    <label>Service Name</label>
                    <select name="lab_service_category_id[${total}]" class="service-fee-select form-control p-0">
                        <option value="">Select Service</option>
                        ${serviceOptionsHtml}
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger remove-service-fee mt-2">Remove</button>
                </div>
            </div>

           <button type="button" class="btn btn-sm btn-outline-primary add-service-options">{{ __('Add Key-Value') }}</button>

        </div>
    `);

        $('#service-fee-container').append(newRow);
    });

    const serviceOptionsHtml = `
        @foreach(App\Models\LabServiceCategory::with('labServices')->get() as $category)
                    <option value="{{ $category->id }}">{{ $category->category_name }}</option>
        @endforeach
`;

    $(document).on('click', '.add-service-options', function() {
        let $parentRow = $(this).closest('.service-fee-row');
        let ServiceId = $parentRow.find('.service-fee-select').val();
        let index = $parentRow.index();

        if (!ServiceId) {
            alert('يرجى اختيار خدمة أولاً');
            return;
        }

        $.ajax({
            url: `/medical-laboratory/serviceOptions/get-options-by-service/${ServiceId}`, // تحتاج إلى إنشاء هذا الراوت في الباك
            method: 'GET',
            success: function(response) {
                let selectOptions = '<option value="">اختر خيارًا</option>';
                response.forEach(function(option) {
                    selectOptions += `<option value="${option.id}"
                    data-name="${option.name}"
                    data-price="${option.price}"
                    data-value="${option.value} ?? ''"
                    data-unit="${option.unit}"
                    data-range="${option.normal_range}">
                    ${option.name}
                </option>`;
                });

                let newRow = `
            <div class="d-flex mb-2 service-options-row">
                <select class="form-control mr-2 option-selector">
                    ${selectOptions}
                </select>
                <input type="text" name="name[]" class="form-control mr-2" placeholder="Name" readonly>
                <input type="text" name="price[]" class="form-control mr-2" placeholder="Price" readonly>
                <input type="text" name="value[]" class="form-control mr-2" placeholder="Value">
                <input type="text" name="unit[]" class="form-control mr-2" placeholder="Unit" readonly>
                <input type="text" name="normal_range[]" class="form-control mr-2" placeholder="Normal Range" readonly>
                <button type="button" class="btn btn-danger remove-service-options">×</button>
            </div>
            `;

                $parentRow.find('.add-service-options').before(newRow);
            },
            error: function() {
                alert('فشل في تحميل الخيارات.');
            }
        });
    });

    $(document).on('change', '.option-selector', function() {
        let selected = $(this).find(':selected');
        let $row = $(this).closest('.service-options-row');

        $row.find('input[name^="name"]').val(selected.data('name'));
        $row.find('input[name^="price"]').val(selected.data('price'));
        $row.find('input[name^="value"]').val(selected.data('value'));
        $row.find('input[name^="unit"]').val(selected.data('unit'));
        $row.find('input[name^="normal_range"]').val(selected.data('range'));
    });


    // Remove service-options input row
    $(document).on('click', '.remove-service-options', function() {
        const $row = $(this).closest('.service-options-row');
        const keyValueId = $row.data('id');

        if (confirm('Are you sure you want to remove this service-options ?')) {
            if (keyValueId) {
                // Send AJAX request to remove from the server
                $.ajax({
                    url: `/medical-laboratory/moduleServiceOptions/delete/${keyValueId}`,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        $row.remove(); // Remove from DOM only after successful delete
                    },
                    error: function() {
                        alert('Failed to delete service-options.');
                    }
                });
            } else {
                // No ID, just remove from DOM
                $row.remove();
            }
        }

    });
</script>
@endpush