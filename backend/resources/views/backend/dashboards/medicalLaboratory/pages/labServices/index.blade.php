@extends('backend.dashboards.medicalLaboratory.layouts.master')

@section('content')
@section('page-header')
<h4 class="page-title">{{__('Service Fees')}} ( {{__('Medical Analysis')}} )</h4>

<div class="page-title-right">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ServiceModal">
        <i class="mdi mdi-plus"></i> {{__('Add Service Fee')}}
    </button>
</div>
@endsection
<div class="container-fluid">


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="Services_table" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{__('ID')}}</th>
                                <th>{{__('Service Name')}}</th>
                                <th>{{__('Category Name')}}</th>
                                <th>{{__('Price')}}</th>
                                <th>{{__('Notes')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Modal -->
<x-modal id="ServiceModal" title="{{ __('Add Service') }}" size="lg">
    <form id="ServiceForm" method="POST">
        @csrf
        <input type="hidden" id="labServiceId">
        <div class="modal-body">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="lab_service_category_id" class="form-label">{{ __('Category') }}</label>
                    <select name="lab_service_category_id" id="lab_service_category_id" class="form-control">
                        @foreach ($serviceCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach

                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="unit" class="form-label">{{ __('Unit') }}</label>
                    <input type="text" class="form-control" id="unit" name="unit" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="normal_range" class="form-label">{{ __('Normal Range') }}</label>
                    <input type="text" class="form-control" id="normal_range" name="normal_range" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="price" class="form-label">{{ __('Price') }}</label>
                    <input type="text" class="form-control" id="price" name="price" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-12 mb-3">
                    <label for="notes" class="form-label">{{ __('Notes') }}</label>
                    <textarea class="form-control" id="notes" name="notes"></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>



        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
</x-modal>

<!-- ServiceOption Modal -->
<x-modal id="serviceOptionModal" title="{{ __('Add Service Option') }}">
    <form id="serviceOptionForm" method="POST">
        @csrf
        <input type="hidden" id="serviceOptionId">
        <input type="hidden" name="service_fee_id" id="service_fee_id">


        <div class="modal-body">
            <div class="key-value-container mb-3 px-3">
                <!-- <label class="d-block">{{ __('Extra Attributes') }}</label> -->
                <div class="d-flex mb-2 key-value-row">
                    <input type="text" name="option_name[0][]" class="form-control mr-2" placeholder="Option Name">
                    <input type="text" name="option_unit[0][]" class="form-control mr-2" placeholder="Option Unit">
                    <input type="text" name="option_normal_range[0][]" class="form-control mr-2" placeholder="Option Normal Range">
                    <button type="button" class="btn btn-danger remove-key-value">×</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary add-key-value">{{ __('Add Key-Value') }}</button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
</x-modal>

<!-- Edit ServiceOption Modal -->
<x-modal id="editServiceOptionModal" title="{{ __('Edit Service Option') }}">
    <form id="editServiceOptionForm" method="POST">
        @csrf
        <input type="hidden" id="editServiceOptionId">
        <input type="hidden" name="service_fee_id" id="edit_service_fee_id">


        <div class="modal-body">
            <div class="key-value-container mb-3 px-3" id="editKeyValueContainer">
                <!-- <label class="d-block">{{ __('Extra Attributes') }}</label> -->
                <div class="d-flex mb-2 key-value-row">
                    <input type="text" name="option_name[]" class="form-control mr-2" placeholder="Option Name">
                    <input type="text" name="option_unit[]" class="form-control mr-2" placeholder="Option Unit">
                    <input type="text" name="option_normal_range[]" class="form-control mr-2" placeholder="Option Normal Range">
                    <button type="button" class="btn btn-danger remove-key-value">×</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary add-key-value">{{ __('Add Key-Value') }}</button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
</x-modal>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const serviceOptionModal = document.getElementById('serviceOptionModal');

        serviceOptionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const ServiceId = button.getAttribute('data-bs-Serviceid');
            document.getElementById('service_fee_id').value = ServiceId;
        });
    });

    let ServicesTable = $('#Services_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('medicalLaboratory.labService.data') }}",
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'category_name',
                name: 'category_name'
            },
            {
                data: 'price',
                name: 'price'
            },
            {
                data: 'notes',
                name: 'notes'
            },

            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            },

        ],
        order: [
            [0, 'desc']
        ],
    });


    // Reset form
    function resetForm() {
        $('#ServiceForm')[0].reset();
        $('#ServiceForm').attr('action', '{{ route("medicalLaboratory.labService.store") }}');
        $('#labServiceId').val('');
        $('#ServiceModal .modal-title').text('{{ __("Add Service Fee") }}');
    }

    // Handle Add/Edit Form Submission
    $('#ServiceForm').on('submit', function(e) {
        e.preventDefault();
        let url = $('#labServiceId').val() ? '{{ route("medicalLaboratory.labService.update", ":id") }}'.replace(':id', $('#labServiceId').val()) : '{{ route("medicalLaboratory.labService.store") }}';
        let method = $('#labServiceId').val() ? 'POST' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                $('#ServiceModal').modal('hide');
                ServicesTable.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function(xhr) {
                // handleValidationErrors(xhr);
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = Object.values(errors).map(function(error) {
                        return error[0];
                    }).join('<br>');

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        html: errorMessages
                    });
                }
            },
        });
    });

    // Edit Service
    function editLabService(id) {
        $.get('{{ route("medicalLaboratory.labService.index") }}/edit/' + id, function(data) {
            $('#labServiceId').val(data.id);
            $('#name').val(data.name);
            $('#lab_service_category_id').val(data.lab_service_category_id);
            $('#unit').val(data.unit);
            $('#normal_range').val(data.normal_range);
            $('#price').val(data.price);

            $('#notes').val(data.notes);

            $('#ServiceModal .modal-title').text('{{ __("Edit Service Fee") }}');
            $('#ServiceModal').modal('show');
        });
    }



    function editServiceOption(ServiceId) {
        $.get(`/medical-laboratory/serviceOptions/edit/${ServiceId}`, function(data) {
            $('#editServiceOptionId').val(ServiceId);
            $('#edit_service_fee_id').val(ServiceId);
            $('#editKeyValueContainer').html(''); // clear previous

            data.forEach((option, index) => {
                $('#editKeyValueContainer').append(`
                <div class="d-flex mb-2 key-value-row">
                    <input type="text" name="option_name[]" class="form-control mr-2" placeholder="Option Name" value="${option.name}">
                    <input type="text" name="option_unit[]" class="form-control mr-2" placeholder="Option Unit" value="${option.unit}">
                    <input type="text" name="option_normal_range[]" class="form-control mr-2" placeholder="Option Normal Range" value="${option.normal_range}">
                    <button type="button" class="btn btn-danger remove-key-value">×</button>
                </div>
            `);
            });
            $('#editKeyValueContainer').append(`
        <button type="button" class="btn btn-sm btn-outline-primary add-key-value">{{ __('Add Key-Value') }}</button>
        `);

            $('#editServiceOptionModal .modal-title').text('Edit Service Options');
            $('#editServiceOptionModal').modal('show');
        });
    }


    $('#editServiceOptionModal').on('hidden.bs.modal', function() {
        // Clear hidden fields
        $('#editServiceOptionId').val('');
        $('#edit_service_fee_id').val('');

        // Clear the key-value container
        $('#editKeyValueContainer').html(`
        <div class="d-flex mb-2 key-value-row">
            <input type="text" name="option_name[0]" class="form-control mr-2" placeholder="Option Name">
            <input type="text" name="option_unit[0]" class="form-control mr-2" placeholder="Option Unit">
            <input type="text" name="option_normal_range[0]" class="form-control mr-2" placeholder="Option Normal Range">
            <button type="button" class="btn btn-danger remove-key-value">×</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary add-key-value">{{ __('Add Key-Value') }}</button>
    `);
    });


    // Delete Service
    function deleteService(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("medicalLaboratory.labService.index") }}/delete/' + id,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token for Laravel
                    },
                    success: function(response) {
                        ServicesTable.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    }

    $(document).on('click', '.add-key-value', function() {
        const newRow = `
        <div class="d-flex mb-2 key-value-row">
            <input type="text" name="option_name[]" class="form-control mr-2" placeholder="Option Name">
            <input type="text" name="option_unit[]" class="form-control mr-2" placeholder="Option Unit">
            <input type="text" name="option_normal_range[]" class="form-control mr-2" placeholder="Option Normal Range">
            <button type="button" class="btn btn-danger remove-key-value">×</button>
        </div>
    `;
        $(this).before(newRow);
    });


    // Remove key-value input row
    $(document).on('click', '.remove-key-value', function() {
        $(this).closest('.key-value-row').remove();
    });

    $('#serviceOptionForm').on('submit', function(e) {
        e.preventDefault();
        let url = $('#serviceOptionId').val() ? '{{ route("medicalLaboratory.serviceOptions.update", ":id") }}'.replace(':id', $('#serviceOptionId').val()) : '{{ route("medicalLaboratory.serviceOptions.store") }}';
        let method = $('#serviceOptionId').val() ? 'POST' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(response) {
                $('#serviceOptionModal').modal('hide');
                ServicesTable.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function(xhr) {
                // handleValidationErrors(xhr);
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = Object.values(errors).map(function(error) {
                        return error[0];
                    }).join('<br>');

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        html: errorMessages
                    });
                }
            },
        });
    });

    $('#editServiceOptionForm').on('submit', function(e) {
        e.preventDefault();
        const ServiceId = $('#editServiceOptionId').val();
        const url = `/medical-laboratory/serviceOptions/update/${ServiceId}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#editServiceOptionModal').modal('hide');
                ServicesTable.ajax.reload();
                Swal.fire('Success', response.message, 'success');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = Object.values(errors).map(function(error) {
                        return error[0];
                    }).join('<br>');

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        html: errorMessages
                    });
                }
            },
        });
    });
</script>
@endpush