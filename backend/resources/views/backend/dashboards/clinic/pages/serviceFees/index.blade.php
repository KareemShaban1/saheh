@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{__('Service Fees')}}
@stop
@endsection
@section('page-header')
<h4 class="page-title">{{__('Service Fees')}}</h4>

<div class="page-title-right">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ServiceModal">
        <i class="mdi mdi-plus"></i> {{__('Add Service Fee')}}
    </button>
</div>

@endsection
@section('content')

<div class="container-fluid">


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="Services_table" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{__('ID')}}</th>
                                <th>{{ __('Doctor') }}</th>
                                <th>{{__('Service Name')}}</th>
                                <th>{{__('Type')}}</th>
                                <th>{{__('Fee')}}</th>
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
<x-modal id="ServiceModal" title="{{ __('Add Service Fee') }}">
    <form id="ServiceForm" method="POST">
        @csrf
        <input type="hidden" id="ServiceId">
        <div class="modal-body">

            <div class="mb-3">
                <label for="doctor_id" class="form-label">{{ __('Doctor') }}</label>
                <select name="doctor_id" id="doctor_id" class="form-control">
                    <option value="">{{ __('Select Doctor') }}</option>
                    @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="service_name" class="form-label">{{ __('Service Name') }}</label>
                <input type="text" class="form-control" id="service_name" name="service_name" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">{{ __('Type') }}</label>
                <select name="type" id="type" class="form-control">
                    <option value="">{{ __('Select Type') }}</option>
                    <option value="main">{{ __('Main') }}</option>
                    <option value="sub">{{ __('Sub') }}</option>
                   
                </select>
            </div>
            <div class="mb-3">
                <label for="fee" class="form-label">{{ __('Fee') }}</label>
                <input type="text" class="form-control" id="fee" name="fee" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                <textarea class="form-control" id="notes" name="notes"></textarea>
                <div class="invalid-feedback"></div>
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
    let ServicesTable = $('#Services_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('clinic.Services.data') }}",
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'doctor',
                name: 'doctor'
            },
            {
                data: 'service_name',
                name: 'service_name'
            },
            {
                data: 'type',
                name: 'type'
            },
            {
                data: 'fee',
                name: 'fee'
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
            }
        ],
        order: [
            [0, 'desc']
        ],
        language: languages[language],
        responsive: true,
        columnDefs: [{
                responsivePriority: 1,
                targets: 1
            }, //  highest priority
            {
                responsivePriority: 2,
                targets: 2
            }, //  lower priority
            {
                responsivePriority: 3,
                targets: 4
            },

            // Add more columnDefs for other columns, if needed
        ],
    });


    // Reset form
    function resetForm() {
        $('#ServiceForm')[0].reset();
        $('#ServiceForm').attr('action', '{{ route("clinic.Services.store") }}');
        $('#ServiceId').val('');
        $('#ServiceModal .modal-title').text('{{ __("Add Service Fee") }}');
    }

    // Handle Add/Edit Form Submission
    $('#ServiceForm').on('submit', function(e) {
        e.preventDefault();
        let url = $('#ServiceId').val() ? '{{ route("clinic.Services.update", ":id") }}'.replace(':id', $('#ServiceId').val()) : '{{ route("clinic.Services.store") }}';
        let method = $('#ServiceId').val() ? 'POST' : 'POST';

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
    function editService(id) {
        console.log(id);
        $.get('{{ route("clinic.Services.index") }}/edit/' + id, function(data) {
            $('#ServiceId').val(data.id);
            $('#service_name').val(data.service_name);
            $('#fee').val(data.fee);
            $('#notes').val(data.notes);

            $('#doctor_id').val(data.doctor_id);
            $('#type').val(data.type);

            $('#ServiceModal .modal-title').text('{{ __("Edit Service Fee") }}');
            $('#ServiceModal').modal('show');
        });
    }

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
                    url: '{{ route("clinic.Services.index") }}/delete/' + id,
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
</script>
@endpush