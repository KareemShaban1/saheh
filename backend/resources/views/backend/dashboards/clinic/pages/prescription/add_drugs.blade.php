<div class="row pt-4">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100">
            <div class="card-body">

                <form action="{{ Route('clinic.prescription.store') }}" method="post" enctype="multipart/form-data"
                    autocomplete="off">
                    @csrf

                    <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                    <input type="hidden" name="patient_id" value="{{ $reservation->patient_id }}">
                    <input type="hidden" name="doctor_id" value="{{ $reservation->doctor_id }}">
                    <input type="hidden" name="clinic_id" value="{{ $reservation->clinic_id }}">

                    <br>

                    <div class="row">
                        <div class="form-group col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 child-repeater-table">
                            <table class="table table-bordered table-responsive" id="table">

                                <thead>
                                    <tr>
                                        <th>{{ trans('backend/drugs_trans.Drug_Name') }}</th>
                                        <th>{{ trans('backend/drugs_trans.Drug_Dose') }}</th>
                                        <th>{{ trans('backend/drugs_trans.Drug_Type') }}</th>
                                        <th>{{ trans('backend/drugs_trans.Frequency') }}</th>
                                        <th>{{ trans('backend/drugs_trans.Period') }}</th>
                                        <th>{{ trans('backend/drugs_trans.Notes') }}</th>
                                        <th><a href="javascript:void(0)" class="btn btn-success addRow">
                                                {{ trans('backend/drugs_trans.Add_Drug') }}
                                            </a></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody">
                                    <tr>

                                      
                                        <td>
                                            <input type="text" name="name[]" class="form-control" style="width:120px"
                                                placeholder="{{ trans('backend/drugs_trans.Drug_Name') }}"
                                                list="drug-name-hints"
                                                autocomplete="off">
                                            <datalist id="drug-name-hints">
                                                <option value="Paracetamol">
                                                <option value="Ibuprofen">
                                                <option value="Acetaminophen">
                                                <option value="Aspirin">
                                                <option value="Diclofenac">
                                                <option value="Naproxen">
                                                <option value="Celecoxib">
                                                <option value="Rofecoxib">
                                                <option value="Indomethacin">
                                                <option value="Sulindac">
                                                <option value="Meloxicam">
                                                <option value="Piroxicam">
                                                <option value="Tenoxicam">
                                                <option value="Phenylbutazone">
                                                <option value="Carbamazepine">
                                                <option value="Oxcarbazepine">
                                                <option value="Topiramate">
                                                <option value="Valproate">
                                                <option value="Gabapentin">
                                                <option value="Pregabalin">
                                                <option value="Vigabatrin">
                                            </datalist>
                                            @error('name')
                                                <p class="alert alert-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="dose[]" class="form-control m-0" style="width:120px"
                                                placeholder="{{ trans('backend/drugs_trans.Drug_Dose') }}"
                                                list="dose-hints"
                                                autocomplete="off">
                                            <datalist id="dose-hints">
                                                <option value="250mg">
                                                <option value="500mg">
                                                <option value="750mg">
                                                <option value="1g">
                                                <option value="2g">
                                                <option value="3g">
                                                <option value="4g">
                                                <option value="5g">
                                                <option value="10mg">
                                                <option value="20mg">
                                                <option value="30mg">
                                                <option value="40mg">
                                                <option value="50mg">
                                                <option value="60mg">
                                                <option value="80mg">
                                                <option value="100mg">
                                                <option value="120mg">
                                                <option value="150mg">
                                                <option value="200mg">
                                                <option value="250mg">
                                                <option value="300mg">
                                                <option value="400mg">
                                                <option value="500mg">
                                                <option value="600mg">
                                                <option value="800mg">
                                                <option value="1000mg">
                                            </datalist>
                                            @error('dose')
                                                <p class="alert alert-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="type[]" class="form-control m-0" style="width:120px"
                                                placeholder="{{ trans('backend/drugs_trans.Drug_Type') }}"
                                                list="drug-type-hints"
                                                autocomplete="off">
                                            <datalist id="drug-type-hints">
                                                <option value="Tablet">
                                                <option value="Capsule">
                                                <option value="Syrup">
                                                <option value="Injection">
                                                <option value="Suppository">
                                                <option value="Topical">
                                                <option value="Ophthalmic">
                                                <option value="Otic">
                                                <option value="Dental">
                                                <option value="Transdermal">
                                                <option value="Vaginal">
                                                <option value="Rectal">
                                                <option value="Urethral">
                                                <option value="Buccal">
                                                <option value="Sublingual">
                                                <option value="Subdermal">
                                                <option value="Nasal">
                                                <option value="Oromucosal">
                                                <option value="Vaginal ring pessary">
                                                <option value="Conjunctival">
                                                <option value="Intrauterine">
                                                <option value="Endourethral">
                                                <option value="Epidural">
                                                <option value="Subarachnoid">
                                                <option value="Intrathecal">
                                                <option value="Epidural">
                                                <option value="Subdural">
                                                <option value="Intracutaneous">
                                                <option value="Subcutaneous">
                                                <option value="Periocular">
                                                <option value="Periorbital">
                                                <option value="Topical (eye)">
                                                <option value="Topical (skin)">
                                                <option value="Topical (ear)">
                                                <option value="Topical (nose)">
                                                <option value="Topical (throat)">
                                                <option value="Topical (vulva)">
                                                <option value="Topical (anus)">
                                                <option value="Topical (rectum)">
                                                <option value="Topical (penis)">
                                                <option value="Topical (scrotum)">
                                                <option value="Topical (testes)">
                                            </datalist>
                                            @error('type')
                                                <p class="alert alert-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="frequency[]" class="form-control m-0" style="width:120px"
                                                placeholder="{{ trans('backend/drugs_trans.Frequency') }}"
                                                list="frequency-hints"
                                                autocomplete="off">
                                            <datalist id="frequency-hints">
                                                <option value="Once a day">
                                                <option value="Twice a day">
                                                <option value="Three times a day">
                                                <option value="Four times a day">
                                                <option value="Once a week">
                                                <option value="Twice a week">
                                                <option value="Three times a week">
                                                <option value="Four times a week">
                                                <option value="Once a month">
                                                <option value="Twice a month">
                                                <option value="Three times a month">
                                                <option value="Four times a month">
                                            </datalist>
                                            @error('frequency')
                                                <p class="alert alert-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="period[]" class="form-control m-0" style="width:120px"
                                                placeholder="{{ trans('backend/drugs_trans.Period') }}"
                                                list="period-hints"
                                                autocomplete="off">
                                            <datalist id="period-hints">
                                                <option value="1 day">
                                                <option value="2 days">
                                                <option value="3 days">
                                                <option value="4 days">
                                                <option value="1 week">
                                                <option value="2 weeks">
                                                <option value="3 weeks">
                                                <option value="4 weeks">
                                                <option value="1 month">
                                                <option value="2 months">
                                                <option value="3 months">
                                                <option value="4 months">
                                                <option value="5 months">
                                                <option value="6 months">
                                                <option value="1 year">
                                                <option value="2 years">
                                                <option value="3 years">
                                                <option value="4 years">
                                                <option value="5 years">
                                                <option value="10 years">
                                                <option value="Until symptoms resolve">
                                                <option value="Until the doctor advises to stop">
                                                <option value="Until the patient is cured">
                                                <option value="Until the patient is discharged from the hospital">
                                                <option value="Until the patient is finished with the treatment">
                                                <option value="Until the patient is no longer taking the medication">
                                                <option value="Until the patient is no longer experiencing side effects">
                                                <option value="Until the patient is no longer experiencing symptoms">
                                                <option value="Until the patient is no longer taking the medication">
                                                <option value="Until the patient is no longer experiencing side effects">
                                                <option value="Until the patient is no longer experiencing symptoms">
                                            </datalist>
                                            @error('period')
                                                <p class="alert alert-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="notes[]" class="form-control m-0" style="width:200px"
                                                placeholder="{{ trans('backend/drugs_trans.Notes') }}">
                                            @error('notes')
                                                <p class="alert alert-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <th><a href="javascript:void(0)" class="btn btn-danger deleteRow">
                                                {{ trans('backend/drugs_trans.Delete') }} </a></th>
                                </tbody>


                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ trans('backend/drugs_trans.Save') }}</button>

                </form>

            </div>
        </div>
    </div>
</div>
