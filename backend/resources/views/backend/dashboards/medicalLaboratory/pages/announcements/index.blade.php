@extends('backend.dashboards.medicalLaboratory.layouts.master')

@section('title')
{{__('Announcements')}}
@endsection

@section('page-header')

<h4 class="page-title">{{ trans('backend/announcements_trans.Announcements') }}</h4>

<div class="page-title-right">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
        <i class="mdi mdi-plus"></i> {{__('Add Announcement')}}
    </button>
</div>

@endsection

@section('content')
<div class="container-fluid">


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="announcements-table" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{__('ID')}}</th>
                                <th>{{__('Title')}}</th>
                                <!-- <th>{{__('Body')}}</th> -->
                                <th>{{__('Active')}}</th>
                                <th>{{__('Type')}}</th>
                                <th>{{__('Send Notification')}}</th>
                                <th>{{__('Start Date')}}</th>
                                <th>{{__('End Date')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Add Announcement Modal -->
<x-modal id="addAnnouncementModal" title="{{__('Add Announcement')}}">
    <form id="addAnnouncementForm" method="POST" action="{{ route('medicalLaboratory.announcements.store') }}">
        @csrf
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Title')}}</label>
                <input type="text" class="form-control" id="title" name="title" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Body')}}</label>
                <textarea class="form-control" id="body" name="body" required></textarea>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Active')}}</label>
                <input type="checkbox" class="toggle-status" id="is_active" name="is_active" checked>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Type')}}</label>
                <select class="form-select" id="type" name="type" required>
                    <option value="text">{{__('Text')}}</option>
                    <option value="banner">{{__('Banner')}}</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Send Notification')}}</label>
                <input type="checkbox" class="toggle-status" id="send_notification" name="send_notification" checked>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('Start Date')}}</label>
                <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">{{__('End Date')}}</label>
                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                <div class="invalid-feedback"></div>
            </div>


        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{__('Close')}}</button>
            <button type="submit" class="btn btn-primary">{{__('Save')}}</button>
        </div>
    </form>
</x-modal>

<!-- Edit Announcement Modal -->
<x-modal id="editAnnouncementModal" title="{{__('Edit Announcement')}}">
    <form id="editAnnouncementForm" method="POST">
        @csrf
        <div class="modal-body">

            <div class="mb-3">
                <label for="edit_title" class="form-label">{{__('Title')}}</label>
                <input type="text" class="form-control" id="edit_title" name="title" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="edit_body" class="form-label">{{__('Body')}}</label>
                <textarea class="form-control" id="edit_body" name="body" required></textarea>
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="edit_is_active" class="form-label">{{__('Active')}}</label>
                <input type="checkbox" class="toggle-status" id="edit_is_active" name="is_active" checked>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="edit_type" class="form-label">{{__('Type')}}</label>
                <select class="form-select" id="edit_type" name="type" required>
                    <option value="text">{{__('Text')}}</option>
                    <option value="banner">{{__('Banner')}}</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="edit_send_notification" class="form-label">{{__('Send Notification')}}</label>
                <input type="checkbox" class="toggle-status" id="edit_send_notification" name="send_notification" checked>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="edit_start_date" class="form-label">{{__('Start Date')}}</label>
                <input type="datetime-local" class="form-control" id="edit_start_date" name="start_date" required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="edit_end_date" class="form-label">{{__('End Date')}}</label>
                <input type="datetime-local" class="form-control" id="edit_end_date" name="end_date" required>
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
        var table = $('#announcements-table').DataTable({
            ajax: "{{ route('medicalLaboratory.announcements.data') }}",
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'title',
                    name: 'title'
                },
                {
                    data: 'is_active',
                    name: 'is_active'
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'send_notification',
                    name: 'send_notification'
                },
                {
                    data: 'start_date',
                    name: 'start_date'
                },
                {
                    data: 'end_date',
                    name: 'end_date'
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
                    title: 'Announcements Data',
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

        // Add Announcement Form Submit
        $('#addAnnouncementForm').on('submit', function(e) {
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
                        $('#addAnnouncementModal').modal('hide');
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

        // Edit Announcement Form Submit
        $('#editAnnouncementForm').on('submit', function(e) {
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
                        $('#editAnnouncementModal').modal('hide');
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

    // Edit Announcement Function
    function editAnnouncement(id) {
        $.get('{{ route("medicalLaboratory.announcements.index") }}/edit/' + id, function(data) {

            console.log(data);
            $('#editAnnouncementModal').modal('show');
            $('#editAnnouncementForm').attr('action', '{{ route("medicalLaboratory.announcements.update", "") }}/' + id);
            $('#edit_title').val(data.title);
            $('#edit_body').val(data.body);
            $('#edit_is_active').prop('checked', data.is_active);
            $('#edit_send_notification').prop('checked', data.send_notification);
            $('#edit_start_date').val(data.start_date);
            $('#edit_end_date').val(data.end_date);
            $('#edit_type').val(data.type);
        })

    }

    function deleteAnnouncement(id) {

        $.ajax({
            url: '{{ route("medicalLaboratory.announcements.delete", "") }}/' + id,
            type: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log(response , response.success);
                if (response.success) {

                    $('#announcements-table').DataTable().ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.message || 'Something went wrong!'
                });
            }
        })

        
    }


</script>
@endpush