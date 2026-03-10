@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{ trans('backend/reservation_inventory_trans.Organization_Inventory') }}
@stop
@endsection
@section('page-header')
<h4 class="page-title">{{ trans('backend/reservation_inventory_trans.Organization_Inventory') }}</h4>

<div class="mb-3">
  <button class="btn btn-primary" id="addOrganizationInventoryBtn">
    <i class="fa fa-plus"></i> {{ trans('backend/reservation_inventory_trans.Add_Inventory') }}
  </button>
</div>

@endsection
@section('content')
<!-- row -->
<div class="row">
  <div class="col-md-12 mb-30">
    <div class="card card-statistics h-100">
      <div class="card-body">

        <table id="organizationInventoriesTable" class="table dt-responsive nowrap w-100">
          <thead>
            <tr>
              <th>{{ trans('backend/reservation_inventory_trans.Id') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Name') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Quantity') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Unit') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Price') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Movements') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Control') }}</th>


            </tr>
          </thead>
        </table>

      </div>
    </div>
  </div>
</div>
<!-- row closed -->

<!-- Add/Edit Organization Inventory Modal -->
<div class="modal fade" id="organizationInventoryModal" tabindex="-1" aria-labelledby="organizationInventoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="organizationInventoryForm">
      @csrf
      <input type="hidden" name="id" id="organizationInventory_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="organizationInventoryModalLabel">{{ trans('backend/reservation_inventory_trans.Add_Inventory') }}</h5>
          <button type="button" class="close" data-dismiss="modal"
            aria-label="الغاء">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body row">

          <!-- 'name',
        'quantity',
        'unit',
        'price',
        'description', -->

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="name" required>

            </div>
          </div>

          <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Quantity') }} <span class="text-danger">*</span></label>
              <input type="number" name="quantity" id="quantity" class="form-control" required>
            </div>
          </div>

          <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Unit') }} <span class="text-danger">*</span></label>
              <input type="text" name="unit" id="unit" class="form-control" required>
            </div>
          </div>

          <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Price') }} <span class="text-danger">*</span></label>
              <input type="number" name="price" id="price" class="form-control" required>
            </div>
          </div>

          <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Description') }}</label>
              <textarea name="description" id="description" class="form-control"></textarea>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="saveOrganizationInventoryBtn">{{ trans('backend/reservation_inventory_trans.Save') }}</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('backend/reservation_inventory_trans.Close') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>


@endsection
@push('scripts')
<script>
  $(document).ready(function() {

    var table = $('#organizationInventoriesTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('clinic.organization-inventories.data') }}",
      columns: [{
          data: 'id',
          name: 'id'
        },
        {
          data: 'name',
          name: 'name'
        },
        {
          data: 'quantity',
          name: 'quantity'
        },
        {
          data: 'unit',
          name: 'unit'
        },
        {
          data: 'price',
          name: 'price'
        },
        {
          data: 'movements',
          name: 'movements'
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
          targets: 3
        },

        // Add more columnDefs for other columns, if needed
      ],

      "drawCallback": function() {
        $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
      }
    });

    const modal = new bootstrap.Modal(document.getElementById('organizationInventoryModal'));

    // Handle add new
    $('#addOrganizationInventoryBtn').on('click', function() {
      $('#organizationInventoryForm')[0].reset();
      $('#organizationInventory_id').val('');
      $('#organizationInventoryModalLabel').text("{{ trans('backend/reservation_inventory_trans.Add_Inventory') }}");
      modal.show();
    });



    // Submit form
    $('#organizationInventoryForm').submit(function(e) {
      e.preventDefault();
      const formData = $(this).serialize();
      const id = $('#organizationInventory_id').val();
      const url = id ? `/clinic/organization-inventories/update/${id}` : `/clinic/organization-inventories/store`;
      const method = 'POST';

      $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
          $('#organizationInventoriesTable').DataTable().ajax.reload();
          $('#organizationInventoryModal').modal('hide');
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
        }
      });
    });


  });

  function editOrganizationInventory(id) {
    $.get('{{ route("clinic.organization-inventories.index") }}/edit/' + id, function(data) {
      $('#organizationInventory_id').val(data.id);
      $('#name').val(data.name);
      $('#quantity').val(data.quantity);
      $('#unit').val(data.unit);
      $('#price').val(data.price);
      $('#description').val(data.description);
      $('#organizationInventoryModal .modal-title').text('{{ __("Edit Organization Inventory") }}');
      $('#organizationInventoryModal').modal('show');
    });
  }

  function deleteOrganizationInventory(id) {
    if (confirm('{{ __("Are you sure you want to delete this organization inventory?") }}')) {
      $.ajax({
        url: '/clinic/organization-inventories/delete/' + id,
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          $('#organizationInventoriesTable').DataTable().ajax.reload();
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
        }
      });
    }
  }
</script>
@endpush