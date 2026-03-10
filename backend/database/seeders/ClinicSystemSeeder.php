<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use Modules\Clinic\User\Models\User;
use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Shared\Patient;
use App\Models\Specialty;
use Modules\Clinic\User\Models\UserDoctor;
use App\Models\Governorate;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Shared\PatientOrganization;
use Illuminate\Support\Facades\Hash;

class ClinicSystemSeeder extends Seeder
{
    public function run()
    {
        // Create roles if they don't exist
        $roles = [
            'clinic-admin',
            'clinic-doctor',
            'clinic-user'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Get existing data
        $governorates = Governorate::all();
        $specialties = specialty::all();
        $cities = City::all();
        $areas = Area::all();

        if ($specialties->isEmpty()) {
            throw new \Exception('Please run specialtySeeder first');
        }

        // Create 10 clinics
        Clinic::factory(10)->create([
            'governorate_id' => fn() => $governorates->random()->id,
            'specialty_id' => fn() => $specialties->random()->id,
            'city_id' => fn() => $cities->random()->id,
            'area_id' => fn() => $areas->random()->id,
        ])->each(function ($clinic) use ($specialties) {
            // For each clinic, create 5 doctors
            for ($i = 0; $i < 5; $i++) {
                // Create user for doctor
                $doctorUser = User::factory()->create([
                    'organization_id' => $clinic->id,
                    'organization_type' => Clinic::class,
                    'password' => Hash::make('password'), // Default password
                ]);

                // Assign doctor role
                $doctorUser->assignRole('clinic-doctor');

                // Create doctor and link to user and clinic
                $doctor = Doctor::factory()->create([
                    'user_id' => $doctorUser->id,
                    'clinic_id' => $clinic->id,
                    'specialty_id' => $specialties->random()->id,
                ]);

                // Create 5 normal users (staff) for each clinic
                for ($j = 0; $j < 5; $j++) {
                    $staffUser = User::factory()->create([
                        'organization_id' => $clinic->id,
                        'organization_type' => Clinic::class,
                        'password' => Hash::make('password'), // Default password
                    ]);

                    // Assign staff role
                    $staffUser->assignRole('clinic-user');

                    // Randomly link staff to 1 or 2 doctors
                    $numDoctors = rand(1, 2);
                    for ($k = 0; $k < $numDoctors; $k++) {
                        UserDoctor::create([
                            'user_id' => $staffUser->id,
                            'doctor_id' => $doctor->id,
                        ]);
                    }
                }
            }
        });

        // Create 5 Medical Laboratories
        MedicalLaboratory::factory(5)->create([
            'governorate_id' => fn() => $governorates->random()->id,
            'city_id' => fn() => $cities->random()->id,
            'area_id' => fn() => $areas->random()->id,
        ]);

        // Create 5 Radiology Centers
        RadiologyCenter::factory(5)->create([
            'governorate_id' => fn() => $governorates->random()->id,
            'city_id' => fn() => $cities->random()->id,
            'area_id' => fn() => $areas->random()->id,
        ]);

        // Create 50 patients and distribute them across organizations
        Patient::factory(50)->create()->each(function ($patient) {
            // Each patient will be assigned to 1-3 random organizations
            $numOrganizations = rand(1, 3);

            // Get all organizations
            $clinics = Clinic::all();
            $medicalLabs = MedicalLaboratory::all();
            $radiologyCenters = RadiologyCenter::all();

            // Create array of possible organization types
            $organizationTypes = [
                ['model' => $clinics, 'type' => Clinic::class],
                ['model' => $medicalLabs, 'type' => MedicalLaboratory::class],
                ['model' => $radiologyCenters, 'type' => RadiologyCenter::class]
            ];

            // Randomly select organizations
            $selectedTypes = array_rand($organizationTypes, $numOrganizations);
            if (!is_array($selectedTypes)) {
                $selectedTypes = [$selectedTypes];
            }

            foreach ($selectedTypes as $typeIndex) {
                $organizationType = $organizationTypes[$typeIndex];
                $organization = $organizationType['model']->random();

                // For clinics, we might want to assign a doctor
                $doctorId = null;
                if ($organizationType['type'] === Clinic::class) {
                    if (rand(0, 1)) { // 50% chance to assign doctor
                        $doctor = Doctor::where('clinic_id', $organization->id)->inRandomOrder()->first();
                        $doctorId = $doctor ? $doctor->id : null;
                    }
                }

                // Create patient organization relationship
                PatientOrganization::create([
                    'patient_id' => $patient->id,
                    'organization_id' => $organization->id,
                    'organization_type' => $organizationType['type'],
                    'doctor_id' => $doctorId,
                    'assigned' => (bool)rand(0, 1),
                ]);
            }
        });
    }
}
