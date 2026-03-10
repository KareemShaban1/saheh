@extends('backend.dashboards.clinic.layouts.master')

@section('title')
{{ trans('backend/patients_trans.Add_Patient_Using_Code') }}
@stop

@section('page-header')
<h4 class="page-title">{{ trans('backend/patients_trans.Add_Patient_Using_Code') }}</h4>
@endsection

@section('content')

<div class="row mb-4">
    <div class="col-md-6">
        <input type="text" id="patient_code" class="form-control" style="background-color: white;" placeholder="{{ trans('backend/patients_trans.Enter_Patient_Code') }}">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary" onclick="searchPatient()">{{ trans('backend/patients_trans.Search') }}</button>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" onclick="startScan()">{{ trans('backend/patients_trans.Scan_QR_Code') }}</button>
    </div>
</div>

<div id="patient_result" style="display:none;">
    <div class="card">
        <div class="card-body">
            <p><strong>{{ trans('backend/patients_trans.Name') }}</strong> <span id="patient_name"></span></p>
            <p><strong>{{ trans('backend/patients_trans.Email') }}</strong> <span id="patient_email"></span></p>
            <button class="btn btn-info" onclick="assignPatient()">{{ trans('backend/patients_trans.Assign_to_Clinic') }}</button>
        </div>
    </div>
</div>

<!-- QR Code Scanner Modal -->
<div id="scannerModal" style="display: none;">
    <video id="preview" style="width: 100%; height: auto;"></video>
</div>



<!-- Doctor Selection Modal -->
<div class="modal fade" id="doctorModal" tabindex="-1" aria-labelledby="doctorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="doctorModalLabel">{{ trans('backend/patients_trans.Select_Doctor') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('backend/patients_trans.Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="doctor_select">{{ trans('backend/patients_trans.Doctor') }}</label>
                    <select id="doctor_select" class="form-control">
                        <option value="">{{ trans('backend/patients_trans.Choose_Doctor') }}</option>
                        {{-- Populate via JavaScript --}}
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="submitAssign()">{{ trans('backend/patients_trans.Assign') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
<script>
    function searchPatient() {
        let code = document.getElementById('patient_code').value;
        if (!code) return alert('Enter a patient code');

        fetch(`/clinic/patients/search?code=${code}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('patient_result').style.display = 'block';
                    document.getElementById('patient_name').innerText = data.patient.name;
                    document.getElementById('patient_email').innerText = data.patient.email;
                    document.getElementById('patient_result').dataset.id = data.patient.id;
                } else {
                    alert(data.message);
                }
            });
    }

    function assignPatient() {
        let patientId = document.getElementById('patient_result').dataset.id;

        $.ajax({
            url: '/clinic/doctors/list',
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    let select = $('#doctor_select');
                    select.empty();
                    select.append('<option value="">{{ trans("backend/patients_trans.Choose_Doctor") }}</option>');

                    data.doctors.forEach(function(doctor) {
                        let name = doctor.user?.name || `Doctor #${doctor.id}`;
                        select.append(`<option value="${doctor.id}">${name}</option>`);
                    });

                    // Store patient ID for use in submitAssign
                    select.data('patient-id', patientId);

                    // Show modal
                    let doctorModal = new bootstrap.Modal(document.getElementById('doctorModal'));
                    doctorModal.show();
                } else {
                    alert(data.message);
                }
            },
            error: function() {
                alert('Error fetching doctors.');
            }
        });
    }

    function submitAssign() {
        let select = $('#doctor_select');
        let doctorId = select.val();
        let patientId = select.data('patient-id');

        if (!doctorId) return alert("{{ trans('backend/patients_trans.Select_Doctor') }}");

        $.ajax({
            url: '/clinic/patients/assign',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            contentType: 'application/json',
            data: JSON.stringify({
                patient_id: patientId,
                doctor_id: doctorId
            }),
            success: function(data) {
                toastr.success(data.message);
                let doctorModalEl = document.getElementById('doctorModal');
                let modalInstance = bootstrap.Modal.getInstance(doctorModalEl);
                modalInstance.hide();
                document.getElementById('patient_result').style.display = 'none';
                document.getElementById('patient_code').value = '';
                document.getElementById('patient_name').innerText = '';
                document.getElementById('patient_email').innerText = '';
            },
            error: function() {
                alert('Error assigning patient.');
            }
        });
    }



    function startScan() {
        document.getElementById('scannerModal').style.display = 'block';
        let scanner = new Instascan.Scanner({
            video: document.getElementById('preview')
        });

        scanner.addListener('scan', function(content) {
            document.getElementById('patient_code').value = content;
            scanner.stop();
            document.getElementById('scannerModal').style.display = 'none';
            searchPatient();
        });

        Instascan.Camera.getCameras().then(function(cameras) {
            if (cameras.length > 0) {
                let selectedCamera = cameras[0]; // default

                // Try to find a camera with 'back' or 'environment' in the name
                cameras.forEach(camera => {
                    if (camera.name.toLowerCase().includes('back') || camera.name.toLowerCase().includes('environment')) {
                        selectedCamera = camera;
                    }
                });

                scanner.start(selectedCamera);
            } else {
                alert('No cameras found');
            }
        }).catch(function(e) {
            console.error(e);
            alert('Error accessing camera');
        });
    }
</script>
@endpush