@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{trans('backend/reservations_trans.Reservation Slots')}}
@stop
@endsection
@section('page-header')

<h4 class="page-title">{{ trans('backend/reservations_trans.Reservation Slots') }}</h4>
<div class="mb-3">
	<button class="btn btn-primary" id="addReservationSlotBtn">
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

				<table id="reservation_slots_table" class="table dt-responsive nowrap w-100">
					<thead>
						<tr>
							<th>{{trans('backend/reservations_trans.Id')}}
							</th>
							<th>{{trans('backend/reservations_trans.Doctor')}}
							</th>
							<th>{{trans('backend/reservations_trans.Reservation_Date')}}
							</th>
							<th>{{trans('backend/reservations_trans.Start_Time')}}
							</th>
							<th>{{trans('backend/reservations_trans.End_Time')}}
							</th>
							<th>{{trans('backend/reservations_trans.Duration')}}
							</th>
							<th>{{trans('backend/reservations_trans.Control')}}
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
<div class="modal fade" id="reservationSlotModal" tabindex="-1" aria-labelledby="reservationSlotModalLabel"
	aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<form id="reservationSlotForm">
			@csrf
			<input type="hidden" name="id" id="reservation_id">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="reservationSlotModalLabel">
						{{ trans('backend/reservations_trans.Add_Reservation') }}
					</h5>
					<button type="button" class="close" data-dismiss="modal"
						aria-label="الغاء">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">

					<div class="row">
						<div class="col-lg-4 col-md-4 col-sm-12">
							<div class="form-group">
								<label> {{trans('backend/reservations_trans.Reservation_Date')}}
									<span
										class="text-danger">*</span></label>
								<input type="date" class="form-control"
									name="date"
									id="reservation_date"
									data-date-format="yyyy-mm-dd"
									required>
								@error('date')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror
							</div>
						</div>
						<div class="col-lg-4 col-md-4 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.Start_Time')}}
								</label>
								<input type="time" name="start_time"
									id="start_time"
									class="form-control" required>
								@error('start_time')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.End_Time')}}
								</label>
								<input type="time" name="end_time"
									id="end_time"
									class="form-control" required>
								@error('end_time')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror
							</div>
						</div>


					</div>

					<div class="row">

						<div class="col-lg-4 col-md-4 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.Doctor')}}
								</label>
								<select name="doctor_id"
									class="form-control"
									id="doctor_id" required>
									<option value="">
										{{trans('backend/reservations_trans.Choose')}}
									</option>
									@foreach ($doctors as $doctor)
									<option
										value="{{$doctor->id}}">
										{{$doctor->user?->name}}
									</option>

									@endforeach
								</select>
								@error('doctor')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror
							</div>
						</div>

						<div class="col-lg-4 col-md-4 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.Duration')}}
								</label>
								<input type="text" name="duration"
									id="duration"
									class="form-control" required>
								@error('duration')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror

							</div>
						</div>


						<div class="col-lg-4 col-md-4 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.Total_Reservation')}}
								</label>
								<input type="text"
									name="total_reservations"
									id="total_reservations"
									class="form-control" required>
								@error('total_reservations')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror
							</div>
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



	var table = $('#reservation_slots_table').DataTable({
		processing: true,
		serverSide: true,
		ajax: "{{ route('clinic.reservation_slots.data') }}",
		columns: [{
				data: 'id',
				name: 'id'
			},
			{
				data: 'doctor',
				name: 'doctor'
			},
			{
				data: 'date',
				name: 'date'
			},
			{
				data: 'start_time',
				name: 'start_time'
			},
			{
				data: 'end_time',
				name: 'end_time'
			},
			{
				data: 'duration',
				name: 'duration'
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
			$('.dataTables_paginate > .pagination')
				.addClass(
					'pagination-rounded');
		}
	});

	const modal = new bootstrap.Modal(document.getElementById('reservationSlotModal'));

	// Handle add new
	$('#addReservationSlotBtn').on('click', function() {
		$('#reservationSlotForm')[0].reset();
		$('#reservation_id').val('');
		$('#reservationSlotModalLabel').text(
			"{{ trans('backend/reservations_trans.Add_Reservation') }}"
			);
		modal.show();
	});

	// Handle edit
	$(document).on('click', '.edit-btn', function() {
		const id = $(this).data('id');
		$.get(`/clinic/reservation_slots/edit/${id}`, function(
		data) {
			$('#reservation_id').val(data
				.id);
			$('#doctor_id').val(data
				.doctor_id
				);
			$('#reservation_date').val(
				data.date);
			$('#start_time').val(data
				.start_time
				);
			$('#end_time').val(data
				.end_time);
			$('#duration').val(data
				.duration);
			$('#total_reservations').val(
				data
				.total_reservations
				);

			$('#reservationSlotModalLabel')
				.text(
					"{{ trans('backend/reservations_trans.Edit_Reservation') }}");
			modal.show();
		});
	});

	// Submit form
	$('#reservationSlotForm').submit(function(e) {
		e.preventDefault();
		const formData = $(this).serialize();
		const id = $('#reservation_id').val();
		const url = id ? `/clinic/reservation_slots/update/${id}` :
			`/clinic/reservation_slots/store`;
		const method = 'POST';

		$.ajax({
			url: url,
			method: method,
			data: formData,
			success: function() {
				$('#reservation_slots_table')
					.DataTable()
					.ajax
					.reload();
				modal
			.hide();
				toastr.success(
					"{{ trans('backend/messages.success') }}");
			},
			error: function(xhr) {
				toastr.error(
					"{{ trans('backend/messages.error') }}");
			}
		});
	});

});
</script>
@endpush
