@extends('backend.dashboards.clinic.layouts.master')


@section('page-header')

<h4 class="page-title">{{ trans('backend/doctors_trans.Doctors') }}</h4>

<div class="page-title-right">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#doctorModal">
        <i class="mdi mdi-plus"></i> {{__('Add Doctor')}}
    </button>
</div>
@endsection

@section('content')



<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <div class="table-responsive p-0">
                    <table id="doctors_table" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{ trans('backend/doctors_trans.Id') }}</th>
                                <th>{{ trans('backend/doctors_trans.Name') }}</th>
                                <th>{{ trans('backend/doctors_trans.Email') }}</th>
                                <th>{{ trans('backend/doctors_trans.Phone') }}</th>
                                <th>{{ trans('backend/doctors_trans.Control') }}</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>


                    </table>
                </div>

            </div>

        </div>
    </div>
</div>


<!-- Doctor Modal -->
<x-modal id="doctorModal" title="{{ __('Add Doctor') }}">
    <form id="doctorForm" method="POST">
        @csrf
        <input type="hidden" id="doctorId">
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input type="password" class="form-control" id="password" name="password">
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                <input type="text" class="form-control" id="phone" name="phone">
                <div class="invalid-feedback"></div>
            </div>


            <div class="mb-3">
                <label for="certifications" class="form-label">{{ __('Certifications') }}</label>
                <textarea name="certifications" id="certifications" class="form-control"></textarea>
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="specialty_id" class="form-label">{{ __('Specializations') }}</label>
                <select name="specialty_id" id="specialty_id" class="form-control">
                    <option value="">{{ __('Select Specialization') }}</option>
                    @foreach($specializations as $specialization)
                    <option value="{{ $specialization->id }}">{{ $specialization->name_ar }}</option>
                    @endforeach
                </select>
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
    var doctorsTable;
    $(document).ready(function() {

        doctorsTable = $('#doctors_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('clinic.doctors.data') }}",
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'phone',
                    name: 'phone'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [0, 'desc']
            ],
            language: languages[language],
            pageLength: 10,
            responsive: true,
            "drawCallback": function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });
    });


    // Reset form
    function resetForm() {
        $('#doctorForm')[0].reset();
        $('#doctorForm').attr('action', '{{ route("clinic.doctors.store") }}');
        $('#doctorId').val('');
        $('#doctorModal .modal-title').text('{{ __("Add Doctor") }}');
    }

    // Handle Add/Edit Form Submission
    $('#doctorForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const doctorId = $('#doctorId').val();
        const url = doctorId ?
            '{{ route("clinic.doctors.update", ":id") }}'.replace(':id', doctorId) :
            '{{ route("clinic.doctors.store") }}';

        const method = doctorId ? 'POST' : 'POST'; // if you're using PUT/PATCH, adjust this

        // Disable submit button and show loading state
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        // Clear previous validation errors (if any)
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();

        $.ajax({
            url: url,
            method: method,
            data: form.serialize(),
            success: function(response) {
                // Hide modal
                $('#doctorModal').modal('hide');

                // Reset form
                form.trigger('reset');
                $('#doctorId').val(''); // Clear hidden ID field

                // Reload datatable
                if (typeof doctorsTable !== 'undefined') {
                    doctorsTable.ajax.reload();
                }

                // Show success alert
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessages = '';

                    // Show validation messages under inputs
                    $.each(errors, function(field, messages) {
                        const input = form.find(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        const feedback = $('<div class="invalid-feedback"></div>').text(messages[0]);
                        input.after(feedback);

                        errorMessages += messages[0] + '<br>';
                    });

                    // Show SweetAlert with validation messages
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMessages
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred. Please try again.'
                    });
                }
            },
            complete: function() {
                // Restore submit button
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
    // Edit doctor
    function editDoctor(id) {
        $.get('{{ route("clinic.doctors.index") }}/edit/' + id, function(data) {
            $('#doctorId').val(data.id);
            $('#name').val(data.name);
            $('#email').val(data.email);
            $('#phone').val(data.phone);
            $('#certifications').val(data.certifications);
            $('#specialty_id').val(data.specialty_id);



            $('#doctorModal .modal-title').text('{{ __("Edit Doctor") }}');
            $('#doctorModal').modal('show');
        });
    }

    // Delete doctor
    function deleteDoctor(id) {
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
                    url: '{{ route("clinic.doctors.index") }}/delete/' + id,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token for Laravel
                    },
                    success: function(response) {
                        doctorsTable.ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    }
                });
            }
        });
    }
</script>
@endpush