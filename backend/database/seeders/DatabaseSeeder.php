<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\SystemControl;
use App\Models\Shared\Patient;
use Database\Factories\PatientFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // \Modules\Clinic\User\Models\User::factory(10)->create();
        $this->call(SpecialtySeeder::class);
        $this->call(GovernorateSeeder::class);
        $this->call(CitySeeder::class);
        $this->call(AreaSeeder::class);
        $this->call(ClinicSeeder::class);
        $this->call(MedicalLaboratorySeeder::class);
        $this->call(RadiologyCenterSeeder::class);
        
        // Create roles and permissions BEFORE creating users
        $this->call(RolePermissionSeeder::class);
        
        // Now create users and assign roles
        $this->call(UserSeeder::class);
        $this->call(DoctorSeeder::class);
        $this->call(PatientSeeder::class);
        $this->call(AdminSeeder::class);

        $this->call(ServiceSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(DoctorReservationAvailabilitySeeder::class);

        // $this->call(SystemControlSeeder::class);
        // $this->call(MedicineSeeder::class);

        // $this->call([
        //     ClinicSystemSeeder::class,
        // ]);

        // \Modules\Clinic\User\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}