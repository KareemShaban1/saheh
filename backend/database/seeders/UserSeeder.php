<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\RadiologyCenter;
use Modules\Clinic\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Get all organizations
        $clinics = Clinic::all();
        $medicalLaboratories = MedicalLaboratory::all();
        $radiologyCenters = RadiologyCenter::all();

        // Create users for each clinic
        foreach ($clinics as $index => $clinic) {
            $this->createClinicUsers($clinic, $index + 1);
        }

        // Create users for each medical laboratory
        foreach ($medicalLaboratories as $index => $lab) {
            $this->createMedicalLaboratoryUsers($lab, $index + 1);
        }

        // Create users for each radiology center
        foreach ($radiologyCenters as $index => $center) {
            $this->createRadiologyCenterUsers($center, $index + 1);
        }
    }

    /**
     * Create users for a clinic
     */
    private function createClinicUsers($clinic, $clinicNumber)
    {
        // Set team context for this clinic
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($clinic->id);

        // Get roles for this clinic
        $adminRole = Role::where('name', 'clinic-admin')
            ->where('guard_name', 'web')
            ->where('team_id', $clinic->id)
            ->first();
        
        $doctorRole = Role::where('name', 'clinic-doctor')
            ->where('guard_name', 'web')
            ->where('team_id', $clinic->id)
            ->first();
        
        $userRole = Role::where('name', 'clinic-user')
            ->where('guard_name', 'web')
            ->where('team_id', $clinic->id)
            ->first();

        // Create admin user
        $admin = $this->upsertUser([
            'name' => "Clinic {$clinicNumber} Admin",
            'email' => "admin@clinic{$clinicNumber}.com",
            'password' => Hash::make('password'),
            'organization_type' => Clinic::class,
            'organization_id' => $clinic->id,
            'job_title'=>'admin'
        ], $adminRole);

        // Create doctors
        for ($i = 1; $i <= 2; $i++) {
            $doctor = $this->upsertUser([
                'name' => "Clinic {$clinicNumber} Doctor {$i}",
                'email' => "doctor{$i}@clinic{$clinicNumber}.com",
                'password' => Hash::make('password'),
                'organization_type' => Clinic::class,
                'organization_id' => $clinic->id,
                'job_title'=>'doctor'
            ], $doctorRole);
        }

        // Create regular users
        for ($i = 1; $i <= 2; $i++) {
            $user = $this->upsertUser([
                'name' => "Clinic {$clinicNumber} User {$i}",
                'email' => "user{$i}@clinic{$clinicNumber}.com",
                'password' => Hash::make('password'),
                'organization_type' => Clinic::class,
                'organization_id' => $clinic->id,
                'job_title'=>'user'
            ], $userRole);
        }
    }

    /**
     * Create users for a medical laboratory
     */
    private function createMedicalLaboratoryUsers($lab, $labNumber)
    {
        // Set team context for this lab
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($lab->id);

        // Get roles for this lab
        $adminRole = Role::where('name', 'medical-laboratory-admin')
            ->where('guard_name', 'medical_laboratory')
            ->where('team_id', $lab->id)
            ->first();
        
        $doctorRole = Role::where('name', 'medical-laboratory-doctor')
            ->where('guard_name', 'medical_laboratory')
            ->where('team_id', $lab->id)
            ->first();
        
        $userRole = Role::where('name', 'medical-laboratory-user')
            ->where('guard_name', 'medical_laboratory')
            ->where('team_id', $lab->id)
            ->first();

        // Create admin user
        $admin = $this->upsertUser([
            'name' => "Medical Lab {$labNumber} Admin",
            'email' => "admin@lab{$labNumber}.com",
            'password' => Hash::make('password'),
            'organization_type' => MedicalLaboratory::class,
            'organization_id' => $lab->id,
            'job_title'=>'admin'
        ], $adminRole);

        // Create lab doctors/technicians
        for ($i = 1; $i <= 2; $i++) {
            $doctor = $this->upsertUser([
                'name' => "Medical Lab {$labNumber} Doctor {$i}",
                'email' => "doctor{$i}@lab{$labNumber}.com",
                'password' => Hash::make('password'),
                'organization_type' => MedicalLaboratory::class,
                'organization_id' => $lab->id,
                'job_title'=>'doctor'
            ], $doctorRole);
        }

        // Create regular users
        for ($i = 1; $i <= 2; $i++) {
            $user = $this->upsertUser([
                'name' => "Medical Lab {$labNumber} User {$i}",
                'email' => "user{$i}@lab{$labNumber}.com",
                'password' => Hash::make('password'),
                'organization_type' => MedicalLaboratory::class,
                'organization_id' => $lab->id,
                'job_title'=>'user'
            ], $userRole);
        }
    }

    /**
     * Create users for a radiology center
     */
    private function createRadiologyCenterUsers($center, $centerNumber)
    {
        // Set team context for this center
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($center->id);

        // Get roles for this center
        $adminRole = Role::where('name', 'radiology-center-admin')
            ->where('guard_name', 'radiology_center')
            ->where('team_id', $center->id)
            ->first();
        
        $doctorRole = Role::where('name', 'radiology-center-doctor')
            ->where('guard_name', 'radiology_center')
            ->where('team_id', $center->id)
            ->first();
        
        $userRole = Role::where('name', 'radiology-center-user')
            ->where('guard_name', 'radiology_center')
            ->where('team_id', $center->id)
            ->first();

        // Create admin user
        $admin = $this->upsertUser([
            'name' => "Radiology Center {$centerNumber} Admin",
            'email' => "admin@radiology{$centerNumber}.com",
            'password' => Hash::make('password'),
            'organization_type' => RadiologyCenter::class,
            'organization_id' => $center->id,
            'job_title'=>'admin'
        ], $adminRole);

        // Create radiologists
        for ($i = 1; $i <= 2; $i++) {
            $doctor = $this->upsertUser([
                'name' => "Radiology Center {$centerNumber} Doctor {$i}",
                'email' => "doctor{$i}@radiology{$centerNumber}.com",
                'password' => Hash::make('password'),
                'organization_type' => RadiologyCenter::class,
                'organization_id' => $center->id,
                'job_title'=>'doctor'
            ], $doctorRole);
        }

        // Create regular users
        for ($i = 1; $i <= 2; $i++) {
            $user = $this->upsertUser([
                'name' => "Radiology Center {$centerNumber} User {$i}",
                'email' => "user{$i}@radiology{$centerNumber}.com",
                'password' => Hash::make('password'),
                'organization_type' => RadiologyCenter::class,
                'organization_id' => $center->id,
                'job_title'=>'user'
            ], $userRole);
        }
    }

    /**
     * Create or update a user by email, then sync role.
     */
    private function upsertUser(array $attributes, ?Role $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $attributes['email']],
            $attributes
        );

        if ($role) {
            $user->syncRoles([$role]);
        }

        return $user;
    }
}
