@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
{{trans('backend/reservations_trans.Number_of_Reservation')}}
@stop
@endsection
@section('page-header')
<h4 class="page-title">{{trans('backend/reservations_trans.Add_Number_of_Reservation')}}</h4>

@endsection
@section('content')
<!-- row -->
<div class="row">
	<div class="col-md-12 mb-30">
		<div class="card card-statistics h-100">
			<div class="card-body">

				@if ($errors->any())
				<div class="alert alert-danger">
					<ul>
						@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
				@endif

				<form method="post" enctype="multipart/form-data"
					action="{{Route('clinic.reservation_numbers.store')}}"
					autocomplete="off">
					@csrf




					<div class="row">
						<div class="col-lg-6 col-md-6 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.Doctor')}}
								</label>
								<select name="doctor_id"
									class="form-control">
									<option value="">
										{{trans('backend/reservations_trans.Select')}}
									</option>
									@foreach ($doctors as $doctor)
									<option
										value="{{$doctor->id}}">
										{{$doctor->user->name}}
									</option>
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label> {{trans('backend/reservations_trans.Reservation_Date')}}
									<span
										class="text-danger">*</span></label>
								<input class="form-control"
									name="reservation_date"
									id="datepicker-action"
									data-date-format="yyyy-mm-dd">
								@error('reservation_date')
								<p class="invalid-feedback">
									{{ $message }}</p>
								@enderror
							</div>
						</div>

						<div class="col-lg-6 col-md-6 col-sm-12">
							<div class="form-group">
								<label>{{trans('backend/reservations_trans.Number_of_Reservations')}}
								</label>
								<input type="text"
									name="num_of_reservations"
									class="form-control">
							</div>
						</div>



					</div>


					<button type="submit"
						class="btn btn-success btn-md nextBtn btn-lg ">{{trans('backend/reservations_trans.Add')}}</button>


				</form>


			</div>
		</div>
	</div>
</div>
<!-- row closed -->
@endsection
@section('js')

@endsection
