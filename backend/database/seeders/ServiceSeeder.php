<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\MedicalLaboratory;
use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $clinic = Clinic::first();
        Service::create([
            'service_name' => 'كشف',
            'organization_id' => $clinic->id,
            'organization_type' => Clinic::class,
            'doctor_id' => 1,
            // 'clinic_id' => 1,
            'price' => 200.00,
            'notes' => 'كشف',
            'type' => 'main',
        ]);

        Service::create([
            'service_name' => 'استشارة',
            // 'clinic_id' => 1,
            'organization_id' => $clinic->id,
            'organization_type' => Clinic::class,
            'doctor_id' => 1,
            'price' => 100.00,
            'notes' => 'استشارة',
            'type' => 'main',
        ]);

        $medicalLaboratory = MedicalLaboratory::first();
        Service::create([
            'service_name' => 'صورة دم كاملة',
            'organization_id' => $medicalLaboratory->id,
            'organization_type' => MedicalLaboratory::class,
            'price' => 200.00,
            'notes' => 'صورة دم كاملة',
            'type' => 'main',
        ]);

        Service::create([
            'service_name' => 'سكر صائم',
            'organization_id' => $medicalLaboratory->id,
            'organization_type' => MedicalLaboratory::class,
            'price' => 200.00,
            'notes' => 'سكر صائم',
            'type' => 'main',
        ]);
    }
}