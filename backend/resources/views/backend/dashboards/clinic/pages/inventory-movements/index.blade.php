@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{ trans('backend/reservation_inventory_trans.Inventory_Movement') }}
@stop
@endsection
@section('page-header')
<h4 class="page-title">{{ trans('backend/reservation_inventory_trans.Inventory_Movement') }}</h4>

<div class="mb-3">
  <button class="btn btn-primary" id="addInventoryMovementBtn">
    <i class="fa fa-plus"></i> {{ trans('backend/reservation_inventory_trans.Add_Inventory_Movement') }}
  </button>
</div>

@endsection
@section('content')
<!-- row -->
<div class="row">
  <div class="col-md-12 mb-30">
    <div class="card card-statistics h-100">
      <div class="card-body">

        <table id="inventoryMovementsTable" class="table dt-responsive nowrap w-100">
          <thead>
            <tr>
              <th>{{ trans('backend/reservation_inventory_trans.Id') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Name') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Quantity') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Type') }}</th>
              <th>{{ trans('backend/reservation_inventory_trans.Control') }}</th>


            </tr>
          </thead>
        </table>

      </div>
    </div>
  </div>
</div>
<!-- row closed -->

<!-- Add/Edit Inventory Movement Modal -->
<div class="modal fade" id="inventoryMovementModal" tabindex="-1" aria-labelledby="inventoryMovementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="inventoryMovementForm">
      @csrf
      <input type="hidden" name="id" id="inventoryMovement_id">
      <input type="hidden" name="inventory_id" id="inventory_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inventoryMovementModalLabel">{{ trans('backend/reservation_inventory_trans.Add_Inventory_Movement') }}</h5>
          <button type="button" class="close" data-dismiss="modal"
            aria-label="الغاء">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body row">

          <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Quantity') }} <span class="text-danger">*</span></label>
              <input type="number" name="quantity" id="quantity" class="form-control" required>
            </div>
          </div>

          <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Type') }} <span class="text-danger">*</span></label>
              <select name="type" id="type" class="form-control" required>
                <option value="">{{ trans('backend/reservation_inventory_trans.Select_Type') }}</option>
                <option value="in">{{ trans('backend/reservation_inventory_trans.In') }}</option>
                <option value="out">{{ trans('backend/reservation_inventory_trans.Out') }}</option>
              </select>
            </div>
          </div>

          <!-- movement date -->
          <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Movement_Date') }} <span class="text-danger">*</span></label>
              <input type="datetime-local" name="movement_date" id="movement_date" class="form-control" required>
            </div>
          </div>


          <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
              <label>{{ trans('backend/reservation_inventory_trans.Notes') }}</label>
              <textarea name="notes" id="notes" class="form-control"></textarea>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="saveInventoryMovementBtn">{{ trans('backend/reservation_inventory_trans.Save') }}</button>
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

    var table = $('#inventoryMovementsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route("clinic.inventory-movements.data", ":inventoryId") }}'.replace(':inventoryId','{{ $inventoryId }}'),      
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
          data: 'type',
          name: 'type'
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

    const modal = new bootstrap.Modal(document.getElementById('inventoryMovementModal'));

    // Handle add new
    $('#addInventoryMovementBtn').on('click', function() {
      $('#inventoryMovementForm')[0].reset();
      $('#inventoryMovement_id').val('');
      $('#inventory_id').val('{{ $inventoryId }}');
      $('#inventoryMovementModalLabel').text("{{ trans('backend/reservation_inventory_trans.Add_Inventory_Movement') }}");
      modal.show();
    });



    // Submit form
    $('#inventoryMovementForm').submit(function(e) {
      e.preventDefault();
      const formData = $(this).serialize();
      const id = $('#inventoryMovement_id').val();
      const url = id ? `/clinic/inventory-movements/update/${id}` : `/clinic/inventory-movements/store`;
      const method = 'POST';

      $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
          $('#inventoryMovementsTable').DataTable().ajax.reload();
          $('#inventoryMovementModal').modal('hide');
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

  function editInventoryMovement(id) {
    $.get('{{ route("clinic.inventory-movements.edit", ":inventory_movement_id") }}'.replace(':inventory_movement_id',id), function(data) {
      $('#inventoryMovement_id').val(data.id);
      $('#inventory_id').val(data.inventory_id);
      $('#quantity').val(data.quantity);
      $('#movement_date').val(data.movement_date);
      $('#type').val(data.type);
      $('#notes').val(data.notes);
      $('#inventoryMovementModalLabel').text('{{ __("Edit Inventory Movement") }}');
      const modal = new bootstrap.Modal(document.getElementById('inventoryMovementModal'));
      modal.show();
    });
  }

  function deleteInventoryMovement(id) {
    if (confirm('{{ __("Are you sure you want to delete this inventory movement?") }}')) {
      $.ajax({
        url: '{{ route("clinic.inventory-movements.delete", ":inventory_movement_id") }}'.replace(':inventory_movement_id',id),
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          $('#inventoryMovementsTable').DataTable().ajax.reload();
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