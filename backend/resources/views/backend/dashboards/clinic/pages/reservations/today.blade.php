@extends('backend.dashboards.clinic.layouts.master')
@section('css')

@section('title')
    {{ trans('backend/reservations_trans.Today_Reservations') }}
@stop

@endsection

@section('page-header')

<h4 class="page-title">{{ trans('backend/reservations_trans.Today_Reservations') }}</h4>

@endsection
@section('content')
<!-- row -->
<div class="row">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <div
                    style="display: flex;
                align-items: center; 
                justify-content:center; margin-bottom:20px">
                    <a href="{{ Route('clinic.reservations.today_reservation_report') }}" class="btn btn-info ">
                        {{ trans('backend/reservations_trans.Daily_Report') }}
                    </a>
                </div>

                <div class="table-responsive">
                    <div class="table-responsive">
                            <table id="today_reservations_table" class="table dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>{{ trans('backend/reservations_trans.Id') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Patient_Name') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Doctor') }}</th>
                                        <!-- <th>{{ trans('backend/reservations_trans.Reservation_Type') }}</th> -->
                                        <th>{{ trans('backend/reservations_trans.Payment') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Reservation_Status') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Acceptance') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Reservation_Date') }}</th>
                                        <th>{{ __('Number / Slot') }}</th>

                                        <!-- <th>{{ trans('backend/reservations_trans.Rays') }}</th> -->

                                        <!-- <th>{{ trans('backend/reservations_trans.Analysis') }}</th> -->

                                        <th>{{ trans('backend/reservations_trans.Chronic_Diseases') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Glasses_Distance') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Prescription') }}</th>
                                        <th>{{ trans('backend/reservations_trans.Control') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- row closed -->

<!-- Swap Number/Slot Modal -->
<div class="modal fade" id="swapSlotModal" tabindex="-1" role="dialog" aria-labelledby="swapSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="swapSlotModalLabel">{{ __('Swap reservation number / slot') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ __('Choose an available number or slot for this day.') }}</p>
                <input type="hidden" id="swap_reservation_id" value="">
                <div class="form-group">
                    <label for="swap_new_value">{{ __('New number / slot') }}</label>
                    <select id="swap_new_value" class="form-control">
                        <option value="">{{ __('Choose') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="swap_submit_btn">{{ __('Swap') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let todayReservationsTable;
    $(document).ready(function() {

        // Today's Reservations Table
        todayReservationsTable = $('#today_reservations_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('clinic.reservations.data') }}",
                data: function(d) {
                    d.date = "{{ now()->toDateString() }}"; // Send today's date as a parameter
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'patient.name',
                    name: 'patient.name'
                },
                {
                    data: 'doctor_name',
                    name: 'doctor_name'
                },
                // {
                //     data: 'type',
                //     name: 'type'
                // },
                {
                    data: 'payment',
                    name: 'payment',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'acceptance',
                    name: 'acceptance',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'number_slot',
                    name: 'number_slot',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'chronic_disease_action',
                    name: 'chronic_disease_action',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'glasses_distance_action',
                    name: 'glasses_distance_action',
                    orderable: false,
                    searchable: false

                },

                {
                    data: 'prescription_action',
                    name: 'prescription_action',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
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
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });

        // Swap slot/number: open modal and load available options
        $(document).on('click', '.btn-swap-slot', function() {
            var reservationId = $(this).data('reservation-id');
            $('#swap_reservation_id').val(reservationId);
            $('#swap_new_value').empty().append('<option value="">' + '{{ __("Choose") }}' + '</option>');

            $.ajax({
                url: "{{ route('clinic.reservations.available_slots_numbers') }}",
                data: { reservation_id: reservationId },
                success: function(res) {
                    if (res.type && res.available && res.available.length) {
                        $.each(res.available, function(i, opt) {
                            $('#swap_new_value').append($('<option>').val(opt.value).text(opt.label));
                        });
                        $('#swapSlotModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: '{{ __("No available options") }}',
                            text: '{{ __("No available reservation numbers or slots for this day.") }}'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : xhr.statusText });
                }
            });
        });

        $('#swap_submit_btn').on('click', function() {
            var reservationId = $('#swap_reservation_id').val();
            var newVal = $('#swap_new_value').val();
            if (!newVal) {
                Swal.fire({ icon: 'warning', title: '{{ __("Please choose a number or slot.") }}' });
                return;
            }
            var payload = { _token: '{{ csrf_token() }}' };
            if (/^\d+$/.test(newVal)) {
                payload.reservation_number = newVal;
            } else {
                payload.slot = newVal;
            }
            $.ajax({
                url: "{{ route('clinic.reservations.swap_slot_number', ['id' => 0]) }}".replace(/\/0$/, '/' + reservationId),
                type: 'POST',
                data: payload,
                success: function(res) {
                    if (res.success) {
                        $('#swapSlotModal').modal('hide');
                        Swal.fire({ icon: 'success', title: res.message });
                        todayReservationsTable.ajax.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: res.message || '{{ __("Error") }}' });
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : xhr.statusText;
                    Swal.fire({ icon: 'error', title: '{{ __("Error") }}', text: msg });
                }
            });
        });
    });

    $(document).on('change', '.res-status-select', function() {
        var reservationId = $(this).data('reservation-id');
        var newStatus = $(this).val();

        $.ajax({
            url: '/clinic/reservations_options/status/' + reservationId,
            type: 'POST',
            data: {
                status: newStatus,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: response.message,

                });
                todayReservationsTable.ajax.reload();

            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseText); // Display error message
            }
        });
    });
</script>
@endpush
