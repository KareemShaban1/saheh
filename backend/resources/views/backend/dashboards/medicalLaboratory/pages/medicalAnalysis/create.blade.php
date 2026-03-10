@extends('backend.dashboards.medicalLaboratory.layouts.master')
@section('css')

@section('title')
{{ trans('backend/medicalAnalysis_trans.Add_Analysis') }}
@stop
@endsection
@section('page-header')
<h4 class="page-title"> {{ trans('backend/medicalAnalysis_trans.Add_Analysis') }}</h4>
@endsection
@section('content')
<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <x-backend.alert />

                <form method="post" enctype="multipart/form-data" action="{{ Route('medicalLaboratory.analysis.store') }}"
                    autocomplete="off">

                    @csrf
                    <div class="row">


                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label for="id"
                                    class="form-control-label">{{ trans('backend/medicalAnalysis_trans.Patient_Name') }}</label>
                                <select name="patient_id" id="patient_id" class="custom-select mr-sm-2">

                                    @foreach ($patients as $patient)
                                    <option value="{{ $patient->id }}" selected>
                                        {{ $patient->name }}
                                    </option>
                                    @endforeach

                                </select>

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label> {{ trans('backend/medicalAnalysis_trans.Analysis_Date') }}<span
                                        class="text-danger">*</span></label>
                                <input class="form-control" name="date" id="datepicker-action"
                                    data-date-format="yyyy-mm-dd">

                            </div>
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label for="payment">{{ trans('backend/medicalAnalysis_trans.Payment') }}<span
                                        class="text-danger">*</span></label>
                                <select name="payment" id="payment" class="custom-select mr-sm-2">
                                    <option value="">{{ trans('backend/medicalAnalysis_trans.Select_Payment') }}</option>
                                    <option value="paid">{{ trans('backend/medicalAnalysis_trans.Paid') }}</option>
                                    <option value="not_paid">{{ trans('backend/medicalAnalysis_trans.Not_Paid') }}</option>
                                </select>

                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>{{ trans('backend/medicalAnalysis_trans.Doctor_Name') }} <span class="text-danger">*</span></label>
                                <input class="form-control" name="doctor_name" type="text">
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="form-group">
                                <label>{{ trans('backend/medicalAnalysis_trans.Cost') }} <span class="text-danger">*</span></label>
                                <input class="form-control" name="cost" type="text">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label>{{ __('Notes') }}</label>
                            <textarea name="report" class="form-control"></textarea>
                        </div>

                    </div>

                    <div id="service-fee-container">
                        <button type="button" class="btn btn-primary mb-3" id="add-service-fee">
                            {{ __('Add Service Fee') }}
                        </button>

                        <div class="service-fee-row">
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label>{{ __('Service Name') }}</label>
                                    <select name="lab_service_category_id[]" class="service-category-select form-control p-0">
                                        <option value="">{{ __('Select Service') }}</option>
                                        @foreach (App\Models\LabServiceCategory::all() as $serviceCategory)
                                        <option value="{{ $serviceCategory->id }}" data-price="{{ $serviceCategory->price }}" data-notes="{{ $serviceCategory->notes }}">
                                            {{ $serviceCategory->category_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger remove-service-fee mt-2">{{ __('Remove') }}</button>
                                </div>
                            </div>

                            <div class="service-options-container mb-3 px-3">
                                <div class="d-flex mb-2 service-options-row">

                                </div>
                            </div>
                        </div>
                    </div>



                    <button type="submit"
                        class="btn btn-success btn-md nextBtn btn-lg">{{ trans('backend/medicalAnalysis_trans.Add') }}</button>

                </form>


            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
@push('scripts')
<script>
    let feeIndex = $('.service-fee-row').length; // start at current count

    // Handle select change: auto-fill fee and notes
    $(document).on('change', '.service-category-select', function() {
        let selected = $(this).find(':selected');
        let fee = selected.data('fee') || '';
        let notes = selected.data('notes') || '';

        let row = $(this).closest('.service-fee-row');
        row.find('.service-fee-input').val(fee);
        row.find('.service-fee-notes').val(notes);

        // 🆕 Fetch and render service options (service-options pairs)
        let ServiceId = selected.val();
        let keyValueContainer = row.find('.service-options-container');

        // Clear old options
        keyValueContainer.find('.service-options-row').remove();
        keyValueContainer.html('');

        console.log(ServiceId)

        if (ServiceId) {
            $.get(`/medical-laboratory/lab-service-options/options/${ServiceId}`, function(data) {
                console.log(data.data)
                if (data.data.length === 0) {
                    keyValueContainer.append(`<div class="text-muted">{{ __('No options available for this service') }}</div>`);
                }


                data.data.forEach((opt, i) => {
                    keyValueContainer.append(`
                    <div class="d-flex mb-2 service-options-row">
                        <input type="hidden" name="lab_service_id[${row.index()}][]" class="form-control mr-2" value="${opt.id}">
                        <input type="text" name="name[${row.index()}][]" class="form-control mr-2" placeholder="Name" value="${opt.name}">
                        <input type="text" name="price[${row.index()}][]" class="form-control mr-2" placeholder="price" value="${opt.price}">
                        <input type="text" name="value[${row.index()}][]" class="form-control mr-2" placeholder="Value" value="">
                        <input type="text" name="unit[${row.index()}][]" class="form-control mr-2" placeholder="Unit" value="${opt.unit}">
                        <input type="text" name="normal_range[${row.index()}][]" class="form-control mr-2" placeholder="Normal Range" value="${opt.normal_range}">
                        
                        <button type="button" class="btn btn-danger remove-service-options">×</button>
                    </div>
                `);
                });
                keyValueContainer.append(`<button type="button" class="btn btn-sm btn-outline-primary add-service-options">{{ __('Add Key-Value') }}</button>`);
            });
        }
    });



    // Remove service fee row
    $(document).on('click', '.remove-service-fee', function() {
        if ($('.service-fee-row').length > 1) {
            $(this).closest('.service-fee-row').remove();
        }
    });


    $(document).on('click', '#add-service-fee', function() {
        let totalRows = $('.service-fee-row').length;

        // Clone the first row
        let newRow = $('.service-fee-row:first').clone();

        // Clear all basic inputs
        newRow.find('select, input, textarea').val('');
        newRow.find('.preview-images').empty();
        newRow.removeAttr('data-id');

        // Update all `name` attributes with the new index
        newRow.find('select[name^="service_fee_id"]').attr('name', `service_fee_id[${totalRows}]`);
        newRow.find('input[name^="service_fee"]').attr('name', `service_fee[${totalRows}]`);
        newRow.find('textarea[name^="service_fee_notes"]').attr('name', `service_fee_notes[${totalRows}]`);
        newRow.find('input[type="file"]').attr('name', `service_fee_images[${totalRows}][]`);

        // Handle Key-Value Inputs
        let keyValueContainer = newRow.find('.service-options-container');
        keyValueContainer.find('.service-options-row').remove(); // remove any existing service-options inputs

        // Append ONE empty service-options input
        keyValueContainer.prepend(`
        <div class="d-flex mb-2 service-options-row">
            <input type="text" name="name[${totalRows}][]" class="form-control mr-2" placeholder="Name">
            <input type="text" name="price[${totalRows}][]" class="form-control mr-2" placeholder="Price">
            <input type="text" name="value[${totalRows}][]" class="form-control mr-2" placeholder="Value">
            <input type="text" name="unit[${totalRows}][]" class="form-control mr-2" placeholder="Unit">
            <input type="text" name="normal_range[${totalRows}][]" class="form-control mr-2" placeholder="Normal Range">
            <button type="button" class="btn btn-danger remove-service-options">×</button>
        </div>
    `);

        // Finally, append the new row
        $('#service-fee-container').append(newRow);
    });

    $(document).on('change', '.service-fee-image-input', function() {
        let previewContainer = $(this).siblings('.preview-images');
        previewContainer.html('');

        Array.from(this.files).forEach(file => {
            let reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.append(`<img src="${e.target.result}" class="img-thumbnail mr-2 mb-2" width="100" height="100">`);
            };
            reader.readAsDataURL(file);
        });
    });

    $(document).on('click', '.add-service-options', function() {
        let row = $(this).closest('.service-fee-row');
        let index = row.index(); // works if rows are not re-ordered

        let newKeyValue = `
        <div class="d-flex mb-2 service-options-row">
            <input type="text" name="name[${index}][]" class="form-control mr-2" placeholder="Name">
            <input type="text" name="price[${index}][]" class="form-control mr-2" placeholder="Price">
            <input type="text" name="value[${index}][]" class="form-control mr-2" placeholder="Value">
            <input type="text" name="unit[${index}][]" class="form-control mr-2" placeholder="Unit">
            <input type="text" name="normal_range[${index}][]" class="form-control mr-2" placeholder="Normal Range">
            <button type="button" class="btn btn-danger remove-service-options">×</button>
        </div>
    `;
        $(this).before(newKeyValue);
    });


    // Remove service-options input row
    $(document).on('click', '.remove-service-options', function() {
        $(this).closest('.service-options-row').remove();
    });
</script>
@endpush