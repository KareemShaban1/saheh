@extends('backend.dashboards.radiologyCenter.layouts.master')
@section('css')

@section('title')
{{ trans('backend/radiologyCenter_trans.Add_Ray') }}
@stop
@endsection
@section('page-header')
<!-- breadcrumb -->
<div class="page-title">
    <div class="row">
        <div class="col-sm-6">
            <h4 class="mb-0"> {{ trans('backend/radiologyCenter_trans.Add_Ray') }} </h4>
        </div>
    </div>
</div>
<!-- breadcrumb -->
@endsection
@section('content')
<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <x-backend.alert />

                <form method="post" enctype="multipart/form-data" action="{{ Route('radiologyCenter.rays.store') }}"
                    autocomplete="off">

                    @csrf
                    <div class="row">


                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label for="id"
                                    class="form-control-label">{{ trans('backend/radiologyCenter_trans.Patient_Name') }}</label>
                                <select name="patient_id" id="patient_id" class="custom-select mr-sm-2">

                                    <option value="{{ $patient->id }}" selected>
                                        {{ $patient->name }}
                                    </option>

                                </select>

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label> {{ trans('backend/radiologyCenter_trans.Date') }}<span
                                        class="text-danger">*</span></label>
                                <input class="form-control" name="date" id="datepicker-action"
                                    data-date-format="yyyy-mm-dd">

                            </div>
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-12 col-12">
                            <div class="form-group">
                                <label for="payment">{{ trans('backend/radiologyCenter_trans.Payment') }}<span
                                        class="text-danger">*</span></label>
                                <select name="payment" id="payment" class="custom-select mr-sm-2">
                                    <option value="">{{ trans('backend/radiologyCenter_trans.Select_Payment') }}</option>
                                    <option value="paid">{{ trans('backend/radiologyCenter_trans.Paid') }}</option>
                                    <option value="not_paid">{{ trans('backend/radiologyCenter_trans.Not_Paid') }}</option>
                                </select>        

                            </div>
                        </div>

                    </div>


                 

                    <div id="service-fee-container">
                        <button type="button" class="btn btn-primary mb-3" id="add-service-fee">
                            {{ __('Add Service Fee') }}
                        </button>

                        <div class="service-fee-row">
                            <div class="row mb-3" style="display: flex;align-items: center;">
                                <div class="col-md-3">
                                    <label>{{ __('Service Name') }}</label>
                                    <select name="service_fee_id[]" class="service-fee-select form-control p-0">
                                        <option value="">{{ __('Select Service') }}</option>
                                        @foreach (App\Models\Service::all() as $Service)
                                        <option value="{{ $Service->id }}"
                                            data-fee="{{ $Service->fee }}"
                                            data-notes="{{ $Service->notes }}">
                                            {{ $Service->service_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('Fee') }}</label>
                                    <input type="number" class="form-control service-fee-input" name="service_fee[]">
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('Notes') }}</label>
                                    <textarea name="service_fee_notes[]" class="form-control service-fee-notes"></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('Images') }}</label>
                                    <input type="file" name="service_fee_images[0][]" class="form-control service-fee-image-input" multiple accept="image/*">
                                    <div class="preview-images mt-2 d-flex flex-wrap"></div>
                                </div>


                                <div class="col-md-3">
                                    <button type="button" class="btn btn-danger remove-service-fee mt-2">{{ __('Remove') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <button type="submit"
                        class="btn btn-success btn-md nextBtn btn-lg">{{ trans('backend/radiologyCenter_trans.Add') }}</button>

                </form>


            </div>
        </div>
    </div>
</div>
<!-- row closed -->
@endsection
@push('scripts')

<script>
    $(document).on('click', '.remove-service-fee', function() {
        $(this).closest('.service-fee-row').remove();
    });

    $(document).on('change', '.service-fee-select', function() {
        var selectedOption = $(this).find(':selected');
        var fee = selectedOption.data('fee');
        var notes = selectedOption.data('notes');

        var row = $(this).closest('.service-fee-row');
        row.find('.service-fee-input').val(fee);
        row.find('.service-fee-notes').val(notes);
    });

    $(document).on('click', '.remove-service-fee', function() {
        $(this).closest('.service-fee-row').remove();
    });


    // Add new service fee row
    $(document).on('click', '#add-service-fee', function() {
        let totalRows = $('.service-fee-row').length;
        let newRow = $('.service-fee-row:first').clone();

        newRow.find('select, input[type="number"], textarea').val('');
        newRow.find('.preview-images').html('');
        newRow.find('input[type="file"]').val('');

        // Update name attributes for all inputs using the new index
        newRow.find('select[name^="service_fee_id"]').attr('name', `service_fee_id[${totalRows}]`);
        newRow.find('input[name^="service_fee"]').attr('name', `service_fee[${totalRows}]`);
        newRow.find('textarea[name^="service_fee_notes"]').attr('name', `service_fee_notes[${totalRows}]`);
        newRow.find('input[type="file"]').attr('name', `service_fee_images[${totalRows}][]`);

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
</script>

@endpush