@extends('backend.dashboards.admin.layouts.master')

@section('page-header')
<h4 class="page-title">{{__('specialties')}}</h4>

<div class="page-title-right">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addspecialtyModal">
        <i class="mdi mdi-plus"></i> {{__('Add specialty')}}
    </button>
</div>
@endsection

@section('content')
<div class="container-fluid">


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="specialties-table" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{__('ID')}}</th>
                                <th>{{__('Name Ar')}}</th>
                                <th>{{__('Name En')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add specialty Modal -->
<x-modal id="addspecialtyModal" title="{{__('Add specialty')}}">
    <form id="addspecialtyForm" method="POST" action="{{ route('admin.specialties.store') }}">
        @csrf
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Name En')}}</label>
                <input type="text" class="form-control" id="name" name="name_en" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Name Ar')}}</label>
                <input type="text" class="form-control" id="name" name="name_ar" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">{{__('Description')}}</label>
                <textarea name="description" id="description" class="form-control"></textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{__('Close')}}</button>
            <button type="submit" class="btn btn-primary">{{__('Save')}}</button>
        </div>
    </form>
</x-modal>

<!-- Edit specialty Modal -->
<x-modal id="editspecialtyModal" title="{{__('Edit specialty')}}">
    <form id="editspecialtyForm" method="POST">
        @csrf
        <div class="modal-body">
            <div class="mb-3">
                <label for="edit_name" class="form-label">{{__('Name En')}}</label>
                <input type="text" class="form-control" id="edit_name_en" name="name_en" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="edit_name" class="form-label">{{__('Name Ar')}}</label>
                <input type="text" class="form-control" id="edit_name_ar" name="name_ar" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">{{__('Description')}}</label>
                <textarea name="description" id="edit_description" class="form-control"></textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{__('Close')}}</button>
            <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
        </div>
    </form>
</x-modal>

@endsection

@push('scripts')
<script>
    $(function() {
        // Initialize DataTable
        var table = $('#specialties-table').DataTable({
            ajax: "{{ route('admin.specialties.data') }}",
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name_ar',
                    name: 'name_ar'
                },
                {
                    data: 'name_en',
                    name: 'name_en'
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
            buttons: [{
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1]
                    }
                },
                {
                    extend: 'excel',
                    text: 'Excel',
                    title: 'specialties Data',
                    exportOptions: {
                        columns: [0, 1]
                    }
                },
                {
                    extend: 'copy',
                    exportOptions: {
                        columns: [0, 1]
                    },
                },
            ],

            dom: '<"d-flex justify-content-between align-items-center mb-3"lfB>rtip',
            pageLength: 10,
            responsive: true,
            language: languages[language],
            "drawCallback": function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });

        // Add specialty Form Submit
        $('#addspecialtyForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        $('#addspecialtyModal').modal('hide');
                        form[0].reset();
                        table.ajax.reload();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            var input = form.find(`[name="${key}"]`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(errors[key][0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON.message || 'Something went wrong!'
                        });
                    }
                }
            });
        });

        // Edit specialty Form Submit
        $('#editspecialtyForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        $('#editspecialtyModal').modal('hide');
                        form[0].reset();
                        table.ajax.reload();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            var input = form.find(`[name="${key}"]`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(errors[key][0]);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON.message || 'Something went wrong!'
                        });
                    }
                }
            });
        });

        // Clear form validation on modal hide
        $('.modal').on('hidden.bs.modal', function() {
            var form = $(this).find('form');
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').text('');
        });
    });

    // Edit specialty Function
    function editspecialty(id, name_en, name_ar,description) {
        var form = $('#editspecialtyForm');
        form.attr('action', `{{ route('admin.specialties.update', '') }}/${id}`);
        form.find('#edit_name_en').val(name_en);
        form.find('#edit_name_ar').val(name_ar);
        form.find('#edit_description').val(description);
        $('#editspecialtyModal').modal('show');
    }
</script>
@endpush
