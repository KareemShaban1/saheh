@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{ trans('backend/reservations_trans.Number_of_Reservations') }}
@stop
@endsection
@section('page-header')
<h4 class="page-title">{{ trans('backend/reservations_trans.Number_of_Reservations') }}</h4>

<div class="mb-3">
	<button class="btn btn-primary" id="addReservationBtn">
		<i class="fa fa-plus"></i> {{ trans('backend/reservations_trans.Add_Reservation') }}
	</button>
</div>

@endsection
@section('content')
<!-- row -->
<div class="row">
	<div class="col-md-12 mb-30">
		<div class="card card-statistics h-100">
			<div class="card-body">

				<table id="num_of_reservations_table" class="table dt-responsive nowrap w-100">
					<thead>
						<tr>
							<th>{{ trans('backend/reservations_trans.Id') }}
							</th>
							<th>{{ trans('backend/reservations_trans.Doctor') }}
							</th>
							<th>{{ trans('backend/reservations_trans.Reservation_Date') }}
							</th>
							<th>{{ trans('backend/reservations_trans.Number_of_Reservations') }}
							</th>
							<th>{{ trans('backend/reservations_trans.Control') }}
							</th>


						</tr>
					</thead>
				</table>

			</div>
		</div>
	</div>
</div>
<!-- row closed -->

<!-- Add/Edit Reservation Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<form id="reservationForm">
			@csrf
			<input type="hidden" name="id" id="reservation_id">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="reservationModalLabel">
						{{ trans('backend/reservations_trans.Add_Reservation') }}
					</h5>
					<button type="button" class="close" data-dismiss="modal"
						aria-label="الغاء">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body row">

					<div class="col-lg-6 col-md-6 col-sm-12">
						<div class="form-group">
							<label>{{ trans('backend/reservations_trans.Doctor') }}</label>
							<select name="doctor_id" class="form-control"
								id="doctor_id">
								<option value="">
									{{ __('Select Doctor') }}
								</option>
								@foreach ($doctors as $doctor)
								<option value="{{ $doctor->id }}">
									{{ $doctor->user->name }}
								</option>
								@endforeach
							</select>
						</div>
					</div>

					<div class="col-md-6">
						<div class="form-group">
							<label>{{ trans('backend/reservations_trans.Reservation_Date') }}
								<span
									class="text-danger">*</span></label>
							<input type="date" class="form-control"
								name="reservation_date"
								id="reservation_date"
								data-date-format="yyyy-mm-dd">

						</div>
					</div>

					<div class="col-lg-6 col-md-6 col-sm-12">
						<div class="form-group">
							<label>{{ trans('backend/reservations_trans.Number_of_Reservations') }}</label>
							<input type="number" name="num_of_reservations"
								id="num_of_reservations"
								class="form-control">
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary"
						id="saveReservationBtn">{{ trans('backend/reservations_trans.Save') }}</button>
					<button type="button" class="btn btn-secondary"
						data-dismiss="modal">{{ trans('backend/reservations_trans.Close') }}</button>
				</div>
			</div>
		</form>
	</div>
</div>


@endsection
@push('scripts')
<script>
$(document).ready(function() {

	var table = $('#num_of_reservations_table').DataTable({
		processing: true,
		serverSide: true,
		ajax: "{{ route('clinic.reservation_numbers.data') }}",
		columns: [{
				data: 'id',
				name: 'id'
			},
			{
				data: 'doctor',
				name: 'doctor'
			},
			{
				data: 'reservation_date',
				name: 'reservation_date'
			},
			{
				data: 'num_of_reservations',
				name: 'num_of_reservations'
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
			$('.dataTables_paginate > .pagination')
				.addClass(
					'pagination-rounded'
				);
		}
	});

	const modal = new bootstrap.Modal(document.getElementById('reservationModal'));

	// Handle add new
	$('#addReservationBtn').on('click', function() {
		$('#reservationForm')[0].reset();
		$('#reservation_id').val('');
		$('#reservationModalLabel').text(
			"{{ trans('backend/reservations_trans.Add_Reservation') }}"
		);
		modal.show();
	});

	// Handle edit
	$(document).on('click', '.edit-btn', function() {
		const id = $(this).data('id');
		$.get(`/clinic/reservation_numbers/edit/${id}`, function(
			data) {
			$('#reservation_id').val(data
				.id);
			$('#doctor_id').val(data
				.doctor_id
			);
			$('#reservation_date').val(
				data
				.reservation_date
			);
			$('#num_of_reservations').val(
				data
				.num_of_reservations
			);
			$('#reservationModalLabel')
				.text(
					"{{ trans('backend/reservations_trans.Edit_Reservation') }}"
				);
			modal.show();
		});
	});

	// Submit form
	$('#reservationForm').submit(function(e) {
		e.preventDefault();
		const formData = $(this).serialize();
		const id = $('#reservation_id').val();
		const url = id ?
			`/clinic/reservation_numbers/update/${id}` :
			`/clinic/reservation_numbers/store`;
		const method = 'POST';

		$.ajax({
			url: url,
			method: method,
			data: formData,
			success: function(response) {
				$('#num_of_reservations_table')
					.DataTable()
					.ajax
					.reload();
				modal
					.hide();
				toastr.success(response
					.message
				);
			},
			error: function(xhr) {
				toastr.error(response
					.message
				);
			}
		});
	});

});
</script>
@endpush
