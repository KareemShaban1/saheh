<div class="row card-body p-2">

    <x-backend.alert />

    <div class="col-12 col-xl-12 col-md-12 col-sm-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <div class="tab nav-border" style="position: relative;">
                    <!-- Main Title -->
                    <div class="d-block d-md-flex justify-content-between">
                        <div class="d-block w-100 col-12 col-sm-3 col-md-3 p-0">
                            <h5 class="card-title">{{ trans('backend/dashboard_trans.Fast_Reserve') }}</h5>
                        </div>
                    </div>

                    <!-- Doctors Tabs -->
                    <ul class="nav nav-tabs" id="doctorsTab" role="tablist">
                        @foreach($doctor_weekly_slots as $doctor_id => $doctor_data)
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                                id="doctor-{{ $doctor_id }}-tab"
                                data-toggle="tab"
                                href="#doctor-{{ $doctor_id }}"
                                role="tab"
                                aria-controls="doctor-{{ $doctor_id }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $doctor_data['doctor']->user->name }}
                            </a>
                        </li>
                        @endforeach
                    </ul>

                    <!-- Doctors Tab Content -->
                    <div class="tab-content" id="doctorsTabContent">
                        @foreach($doctor_weekly_slots as $doctor_id => $doctor_data)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                            id="doctor-{{ $doctor_id }}"
                            role="tabpanel"
                            aria-labelledby="doctor-{{ $doctor_id }}-tab">

                            <!-- Date Tabs for this Doctor -->
                            <div class="d-block d-md-flex justify-content-center nav-tabs-custom col-12 p-0 mt-3">
                                <ul class="nav nav-tabs" id="dateTab-{{ $doctor_id }}" role="tablist">
                                    @foreach($doctor_data['weekly_slots'] as $index => $day_slot)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $day_slot['active_class'] }}"
                                            id="{{ $doctor_id }}-{{ $day_slot['date'] }}-tab"
                                            data-toggle="tab"
                                            href="#{{ $doctor_id }}-{{ $day_slot['date'] }}"
                                            role="tab"
                                            aria-controls="{{ $doctor_id }}-{{ $day_slot['date'] }}"
                                            aria-selected="{{ $day_slot['active_class'] ? 'true' : 'false' }}">
                                            {{ $day_slot['date'] }}
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>

                            <!-- Date Tab Content for this Doctor -->
                            <div class="tab-content py-3" id="dateTabContent-{{ $doctor_id }}">
                                @foreach($doctor_data['weekly_slots'] as $index => $day_slot)
                                <div class="tab-pane fade {{ $day_slot['active_class'] }}"
                                    id="{{ $doctor_id }}-{{ $day_slot['date'] }}"
                                    role="tabpanel"
                                    aria-labelledby="{{ $doctor_id }}-{{ $day_slot['date'] }}-tab">

                                    <!-- Numbered Reservations -->
                                    @if($day_slot['number_of_reservations'] > 0)
                                    <div class="reservation-numbers">
                                        @for($j = 1; $j <= $day_slot['number_of_reservations']; $j++)
                                            @php
                                            $reserved=Modules\Clinic\Reservation\Models\Reservation::where('date', $day_slot['date'])
                                            ->where('reservation_number', $j)
                                            ->where('doctor_id', $doctor_id)
                                            ->exists();
                                            @endphp

                                            @if($reserved)
                                            <button class="btn btn-danger btn-lg m-2" disabled>
                                                {{ $j }}
                                            </button>
                                            @else
                                            <button class="btn btn-info btn-lg m-2" data-toggle="modal"
                                                data-target="#addReservationModal-{{ $doctor_id }}-{{ $day_slot['date'] }}-{{ $j }}">
                                                {{ $j }}
                                            </button>
                                            @endif

                                            <!-- Numbered Reservation Modal -->
                                            <div class="modal fade" id="addReservationModal-{{ $doctor_id }}-{{ $day_slot['date'] }}-{{ $j }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                {{ trans('backend/dashboard_trans.Add_Reservation') }} #{{ $j }}
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="post" action="{{ route('clinic.reservations.store') }}">
                                                                @csrf
                                                                <input type="hidden" name="doctor_id" value="{{ $doctor_id }}">
                                                                <input type="hidden" name="date" value="{{ $day_slot['date'] }}">
                                                                <input type="hidden" name="reservation_number" value="{{ $j }}">

                                                                <div class="row">
                                                                
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>{{ trans('backend/reservations_trans.Patient_Name') }}</label>
                                                                            <select name="patient_id" class="form-control" required>
                                                                                <option value="">{{ trans('backend/reservations_trans.Choose') }}</option>
                                                                                @foreach($patients as $patient)
                                                                                <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>{{ trans('backend/reservations_trans.Reservation_Type') }}</label>
                                                                            <select name="type" class="form-control" required>
                                                                                <option value="check">{{ trans('backend/reservations_trans.Check') }}</option>
                                                                                <option value="recheck">{{ trans('backend/reservations_trans.Recheck') }}</option>
                                                                                <option value="consultation">{{ trans('backend/reservations_trans.Consultation') }}</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <!-- <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label>{{ trans('backend/reservations_trans.Cost') }}</label>
                                                                            <input type="number" name="cost" class="form-control" required>
                                                                        </div>
                                                                    </div> -->
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label>{{ trans('backend/reservations_trans.Payment') }}</label>
                                                                            <select name="payment" class="form-control" required>
                                                                                <option value="paid">{{ trans('backend/reservations_trans.Paid') }}</option>
                                                                                <option value="not_paid">{{ trans('backend/reservations_trans.Not_Paid') }}</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="form-group">
                                                                            <label>{{ trans('backend/reservations_trans.Reservation_Status') }}</label>
                                                                            <select name="status" class="form-control" required>
                                                                                <option value="waiting">{{ trans('backend/reservations_trans.Waiting') }}</option>
                                                                                <option value="entered">{{ trans('backend/reservations_trans.Entered') }}</option>
                                                                                <option value="finished">{{ trans('backend/reservations_trans.Finished') }}</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div id="service-fee-container-numbers-{{ $j }}" class="service-fee-container-numbers">
                                                                    <button type="button" class="btn btn-primary mb-3 add-service-fee-numbers"
                                                                        id="add-service-fee-numbers"
                                                                        data-target="service-fee-container-numbers-{{ $j }}">
                                                                        {{ __('Add Service Fee') }}
                                                                    </button>

                                                                    <div class="service-fee-row-numbers">
                                                                        <div class="row mb-3" style="display: flex;align-items: center;">
                                                                            <div class="col-md-3">
                                                                                <label>{{ __('Service Name') }}</label>
                                                                                <select name="service_fee_id[]" class="service-fee-select-numbers form-control p-0">
                                                                                    <option value="">{{ __('Select Service') }}</option>
                                                                                    @foreach (App\Models\Service::all() as $Service)
                                                                                    <option value="{{ $Service->id }}"
                                                                                        data-fee="{{ $Service->fee }}"
                                                                                        data-notes="{{ $Service->notes }}">
                                                                                        {{ $Service->service_name }}
                                                                                    </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <label>{{ __('Fee') }}</label>
                                                                                <input type="number" class="form-control service-fee-input" name="service_fee[]">
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <label>{{ __('Notes') }}</label>
                                                                                <textarea name="service_fee_notes[]" class="form-control service-fee-notes"></textarea>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <button type="button" class="btn btn-danger remove-service-fee-numbers mt-2">{{ __('Remove') }}</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>{{ trans('backend/reservations_trans.First_Diagnosis') }}</label>
                                                                    <textarea name="first_diagnosis" class="form-control summernote"></textarea>
                                                                </div>

                                                                <div class="text-right">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                                        {{ trans('backend/reservations_trans.Close') }}
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        {{ trans('backend/reservations_trans.Add') }}
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endfor
                                    </div>

                                    <!-- Time Slot Reservations -->
                                    @elseif(!empty($day_slot['slots']))
                                    <div class="row">
                                        @foreach($day_slot['slots'] as $slot_index => $slot)
                                        @php
                                        $reserved = Modules\Clinic\Reservation\Models\Reservation::where('date', $day_slot['date'])
                                        ->where('slot', $slot['slot_start_time'])
                                        ->where('doctor_id', $doctor_id)
                                        ->exists();

                                        @endphp

                                        <div class="col-md-3 mb-3">
                                            @if($reserved)
                                            <button class="btn btn-danger btn-block" disabled>
                                                {{ $slot['slot_start_time'] }} - {{ $slot['slot_end_time'] }}
                                            </button>
                                            @else
                                            <button class="btn btn-info btn-block" data-toggle="modal"
                                                data-target="#addSlotReservationModal-{{ $doctor_id }}-{{ $day_slot['date'] }}-{{ $slot_index }}">
                                                {{ $slot['slot_start_time'] }} - {{ $slot['slot_end_time'] }}
                                            </button>
                                            @endif
                                        </div>

                                        <!-- Time Slot Reservation Modal -->
                                        <div class="modal fade" id="addSlotReservationModal-{{ $doctor_id }}-{{ $day_slot['date'] }}-{{ $slot_index }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            {{ trans('backend/dashboard_trans.Add_Reservation') }} ({{ $slot['slot_start_time'] }} - {{ $slot['slot_end_time'] }})
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="post" action="{{ route('clinic.reservations.store') }}">
                                                            @csrf
                                                            <input type="hidden" name="doctor_id" value="{{ $doctor_id }}">
                                                            <input type="hidden" name="date" value="{{ $day_slot['date'] }}">
                                                            <input type="hidden" name="slot" value="{{ $slot['slot_start_time'] }}">

                                                            <div class="row">


                                                                

                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>{{ trans('backend/reservations_trans.Patient_Name') }}</label>
                                                                        <select name="patient_id" class="form-control" required>
                                                                            <option value="">{{ trans('backend/reservations_trans.Choose') }}</option>
                                                                            @foreach($patients as $patient)
                                                                            <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>{{ trans('backend/reservations_trans.Reservation_Type') }}</label>
                                                                        <select name="type" class="form-control" required>
                                                                            <option value="check">{{ trans('backend/reservations_trans.Check') }}</option>
                                                                            <option value="recheck">{{ trans('backend/reservations_trans.Recheck') }}</option>
                                                                            <option value="consultation">{{ trans('backend/reservations_trans.Consultation') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <!-- <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>{{ trans('backend/reservations_trans.Cost') }}</label>
                                                                        <input type="number" name="cost" class="form-control" required>
                                                                    </div>
                                                                </div> -->
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>{{ trans('backend/reservations_trans.Payment') }}</label>
                                                                        <select name="payment" class="form-control" required>
                                                                            <option value="paid">{{ trans('backend/reservations_trans.Paid') }}</option>
                                                                            <option value="not_paid">{{ trans('backend/reservations_trans.Not_Paid') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>{{ trans('backend/reservations_trans.Reservation_Status') }}</label>
                                                                        <select name="res_status" class="form-control" required>
                                                                            <option value="waiting">{{ trans('backend/reservations_trans.Waiting') }}</option>
                                                                            <option value="entered">{{ trans('backend/reservations_trans.Entered') }}</option>
                                                                            <option value="finished">{{ trans('backend/reservations_trans.Finished') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div id="service-fee-container-slots-{{ $slot_index }}" class="service-fee-container-slots">
                                                                <button type="button" class="btn btn-primary mb-3 add-service-fee-slots"
                                                                    id="add-service-fee"
                                                                    data-target="service-fee-container-slots-{{ $slot_index }}">
                                                                    {{ __('Add Service Fee') }}
                                                                </button>

                                                                <div class="service-fee-row-slots">
                                                                    <div class="row mb-3" style="display: flex;align-items: center;">
                                                                        <div class="col-md-3">
                                                                            <label>{{ __('Service Name') }}</label>
                                                                            <select name="service_fee_id[]" class="service-fee-select-slots form-control p-0">
                                                                                <option value="">{{ __('Select Service') }}</option>
                                                                                @foreach (App\Models\Service::all() as $Service)
                                                                                <option value="{{ $Service->id }}"
                                                                                    data-fee="{{ $Service->fee }}"
                                                                                    data-notes="{{ $Service->notes }}">
                                                                                    {{ $Service->service_name }}
                                                                                </option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <label>{{ __('Fee') }}</label>
                                                                            <input type="number" class="form-control service-fee-input" name="service_fee[]">
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <label>{{ __('Notes') }}</label>
                                                                            <textarea name="service_fee_notes[]" class="form-control service-fee-notes"></textarea>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <button type="button" class="btn btn-danger remove-service-fee-slots mt-2">{{ __('Remove') }}</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                                <!-- Service Fee Section -->
                                                                <div id="service-fee-container">
                                                                    <button type="button" class="btn btn-primary mb-3" id="add-service-fee">
                                                                        {{ __('Add Service Fee') }}
                                                                    </button>

                                                                    <div class="service-fee-row">
                                                                        <div class="row mb-3" style="display: flex;align-items: center;">
                                                                            <div class="col-md-3">
                                                                                <label>{{ __('Service Name') }}</label>
                                                                                <select name="service_fee_id[]" class="service-fee-select form-control p-0">
                                                                                    <option value="">{{ __('Select Service') }}</option>
                                                                                    @foreach (App\Models\Service::all() as $Service)
                                                                                    <option value="{{ $Service->id }}"
                                                                                        data-fee="{{ $Service->fee }}"
                                                                                        data-notes="{{ $Service->notes }}">
                                                                                        {{ $Service->service_name }}
                                                                                    </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <label>{{ __('Fee') }}</label>
                                                                                <input type="number" class="form-control service-fee-input" name="service_fee[]">
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <label>{{ __('Notes') }}</label>
                                                                                <textarea name="service_fee_notes[]" class="form-control service-fee-notes"></textarea>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <button type="button" class="btn btn-danger remove-service-fee mt-2">{{ __('Remove') }}</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>



                                                            <div class="form-group">
                                                                <label>{{ trans('backend/reservations_trans.First_Diagnosis') }}</label>
                                                                <textarea name="first_diagnosis" class="form-control summernote"></textarea>
                                                            </div>

                                                            <div class="text-right">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                                    {{ trans('backend/reservations_trans.Close') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    {{ trans('backend/reservations_trans.Add') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>


            </div>
        </div>
    </div>


</div>
