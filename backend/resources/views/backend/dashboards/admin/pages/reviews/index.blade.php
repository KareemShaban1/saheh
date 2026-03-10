@extends('backend.dashboards.admin.layouts.master')

@section('title')
    {{ trans('backend/patient_reviews.Reviews') }}
@endsection

@section('page-header')
    <h4 class="page-title">{{ trans('backend/patient_reviews.Reviews') }}</h4>
@endsection

@section('content')
    <div class="page-title">
        <div class="row">
            <div class="col-sm-6">
                    <h4 class="mb-0">{{ __('backend/patient_reviews.Reviews') }}</h4>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right ">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"
                            class="default-color">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Reviews') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 mb-30">
            <div class="card card-statistics h-100">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-striped table-bordered p-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Organization') }}</th>
                                    <th>{{ __('Doctor') }}</th>
                                    <th>{{ __('Patient') }}</th>
                                    <th>{{ __('Rating') }}</th>
                                    <th>{{ __('Comment') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Review Modal -->
    <div class="modal fade" id="editReviewModal" tabindex="-1" aria-labelledby="editReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReviewModalLabel">{{ __('Edit Review') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editReviewForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_rating" class="form-label">{{ __('Rating') }}</label>
                            <select class="form-control" id="edit_rating" name="rating" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_comment" class="form-label">{{ __('Comment') }}</label>
                            <textarea class="form-control" id="edit_comment" name="comment" required rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Switch styles */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #28a745;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #28a745;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    /* Status text styles */
    .text-success {
        color: #28a745 !important;
        font-weight: 600;
    }

    .text-danger {
        color: #dc3545 !important;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.reviews.data') }}",
                columns: [{
                        data: 'organization_name',
                        name: 'organization_name'
                    },
                    {
                        data: 'doctor_name',
                        name: 'doctor_name'
                    },
                    {
                        data: 'patient_name',
                        name: 'patient_name'
                    },
                    {
                        data: 'rating',
                        name: 'rating'
                    },
                    {
                        data: 'comment',
                        name: 'comment'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Edit Review Form Submit
            $('#editReviewForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#editReviewModal').modal('hide');
                            form[0].reset();
                            $('#datatable').DataTable().ajax.reload();
                            toastr.success(response.message);
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
                            toastr.error(xhr.responseJSON.message || 'Something went wrong!');
                        }
                    }
                });
            });

            // Toggle Status Change Event
            $(document).on('change', '.toggle-status', function() {
                var $toggle = $(this);
                var reviewId = $toggle.data('id');
                var newStatus = $toggle.is(':checked') ? 1 : 0;

                $.ajax({
                    url: "{{ route('admin.reviews.update-status') }}",
                    type: 'POST',
                    data: {
                        id: reviewId,
                        is_active: newStatus,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            toastr.success(response.message);
                            // Update the status text and class
                            $('#status-text-' + reviewId)
                                .text(response.newStatus)
                                .removeClass('text-success text-danger')
                                .addClass(response.statusClass);
                        } else {
                            toastr.error('Failed to update status.');
                            $toggle.prop('checked', !$toggle.prop('checked')); // Revert UI if failed
                        }
                    },
                    error: function() {
                        toastr.error('Error updating review status.');
                        $toggle.prop('checked', !$toggle.prop('checked')); // Revert UI on error
                    }
                });
            });

            // Clear form validation on modal hide
            $('#editReviewModal').on('hidden.bs.modal', function() {
                var form = $(this).find('form');
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            });
        });

        function editReview(id) {
            $.get("{{ route('admin.reviews.show', '') }}/" + id, function(data) {
                var form = $('#editReviewForm');
                form.attr('action', "{{ route('admin.reviews.update', '') }}/" + id);
                form.find('#edit_rating').val(data.rating);
                form.find('#edit_comment').val(data.comment);
                $('#editReviewModal').modal('show');
            });
        }

        function deleteRecord(id) {
            if (confirm("{{ __('Are you sure you want to delete this review?') }}")) {
                $.ajax({
                    url: "{{ route('admin.reviews.delete', '') }}/" + id,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#datatable').DataTable().ajax.reload();
                            toastr.success(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message);
                    }
                });
            }
        }
    </script>
@endpush
