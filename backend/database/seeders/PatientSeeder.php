<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\Shared\Patient;
use App\Models\RadiologyCenter;
use Database\Factories\PatientFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        $patient = Patient::create([
            'name' => 'كريم شعبان',
            'age'=> '26',
            'address'=> 'بنها',
            'email'=>'kareem@patient.com',
            'password'=>Hash::make('password'),
            'phone'=> '0123456789',
            'whatsapp_number'=> '0123456789',
            'blood_group'=> 'A+',
            'gender'=> 'male',
            'height'=> '180',
            'weight'=> '80',
            'marital_status'=> 'single',
            'active'=> true,
        ]);

        foreach (Clinic::take(4)->get() as $clinic) {
            if ($clinic->doctors->isEmpty()) {
                continue; // skip clinics with no doctors
            }

            DB::table('patient_organization')->insert([
                'patient_id'         => $patient->id,
                'organization_id'    => $clinic->id,
                'organization_type'  => Clinic::class,
                'doctor_id'          => $clinic->doctors->random()->id,
            ]);
        }


        foreach (MedicalLaboratory::take(4)->get() as $medicalLaboratory) {
            DB::table('patient_organization')->insert([
                'patient_id'=> $patient->id,
                'organization_id'=> $medicalLaboratory->id,
                'organization_type'=> MedicalLaboratory::class,
                'doctor_id'=> null,
            ]);
        }

        foreach (RadiologyCenter::take(4)->get() as $radiologyCenter) {
            DB::table('patient_organization')->insert([
                'patient_id'=> $patient->id,
                'organization_id'=> $radiologyCenter->id,
                'organization_type'=> RadiologyCenter::class,
                'doctor_id'=> null,
            ]);
        }


        $patients = PatientFactory::new()->count(10)->create();
        foreach ($patients as $patient) {
            foreach (Clinic::take(4)->get() as $clinic) {
                if ($clinic->doctors->isEmpty()) {
                    continue; // skip clinics with no doctors
                }

                DB::table('patient_organization')->insert([
                    'patient_id'         => $patient->id,
                    'organization_id'    => $clinic->id,
                    'organization_type'  => Clinic::class,
                    'doctor_id'          => $clinic->doctors->random()->id,
                ]);
            }

            foreach (MedicalLaboratory::take(2)->get() as $medicalLaboratory) {
                DB::table('patient_organization')->insert([
                    'patient_id'=> $patient->id,
                    'organization_id'=> $medicalLaboratory->id,
                    'organization_type'=> MedicalLaboratory::class,
                    'doctor_id'=> null,
                ]);
            }
            foreach (RadiologyCenter::take(2)->get() as $radiologyCenter) {
                DB::table('patient_organization')->insert([
                    'patient_id'=> $patient->id,
                    'organization_id'=> $radiologyCenter->id,
                    'organization_type'=> RadiologyCenter::class,
                    'doctor_id'=> null,
                ]);
            }
        }



    }
}