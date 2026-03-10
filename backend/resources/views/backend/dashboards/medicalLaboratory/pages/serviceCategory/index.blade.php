@extends('backend.dashboards.medicalLaboratory.layouts.master')


@section('content')

@section('page-header')
<h4 class="page-title">{{__('Service Categories')}} ( {{__('Medical Analysis')}} )</h4>

<div class="page-title-right">
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#categoryModal" id="addCategoryBtn">
    {{ __('Add Category') }}
</button>

</div>
@endsection

<div class="container-fluid">


    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="serviceCategoryTable" class="table dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{__('ID')}}</th>
                                <th>{{__('Category Name')}}</th>
                                <th>{{__('Active')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="categoryForm">
            @csrf
            <input type="hidden" name="id" id="category_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">{{ __('Add Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">{{ __('Category Name') }}</label>
                        <input type="text" class="form-control" name="category_name" id="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="is_active" class="form-label">{{ __('Is Active') }}</label>
                        <select class="form-select" name="is_active" id="is_active" required>
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Inactive') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')

<script>

let serviceCategoryTable = $('#serviceCategoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('medicalLaboratory.serviceCategory.data') }}",
        columns: [{
                data: 'id',
                name: 'id'
            },
            {
                data: 'category_name',
                name: 'category_name'
            },
            {
                data: 'is_active',
                name: 'is_active'
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


    // Open modal for add
$('#addCategoryBtn').on('click', function () {
    $('#categoryModalLabel').text('{{ __("Add Category") }}');
    $('#categoryForm')[0].reset();
    $('#category_id').val('');
});

// Handle edit click (delegate because buttons are dynamically loaded)
$(document).on('click', '.edit-category', function () {
    const data = $(this).data();
    $('#categoryModalLabel').text('{{ __("Edit Category") }}');
    $('#category_id').val(data.id);
    $('#category_name').val(data.name);
    $('#is_active').val(data.active);
    $('#categoryModal').modal('show');
});

// Submit form
$('#categoryForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#category_id').val();
    const url = id
        ? `/medical-laboratory/service-categories/${id}`
        : `{{ route('medicalLaboratory.serviceCategory.store') }}`;
    const method = id ? 'PUT' : 'POST';

    $.ajax({
        url,
        method,
        data: $(this).serialize(),
        success: function (res) {
            $('#categoryModal').modal('hide');
            serviceCategoryTable.ajax.reload(null, false);
            toastr.success(res.message || '{{ __("Saved successfully") }}');
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON.message || '{{ __("Something went wrong") }}');
        }
    });
});

function deleteServiceCategory(id) {
        if (!confirm("{{ __('Are you sure you want to delete this category?') }}")) return;

        $.ajax({
            url: '{{ route("medicalLaboratory.serviceCategory.destroy", ":id") }}'.replace(':id', id),
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#serviceCategoryTable').DataTable().ajax.reload();
                } else {
                    toastr.error("{{ __('Something went wrong.') }}");
                }
            },
            error: function(xhr) {
                toastr.error("{{ __('Failed to delete category.') }}");
            }
        });
    }

</script>

@endpush