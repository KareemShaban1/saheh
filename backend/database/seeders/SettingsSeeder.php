<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('settings')->delete();

        $data = [
            // ['key' => 'doctor_name', 'value' => 'kareem shaban'],
            // ['key' => 'doctor_address', 'value' => 'Benha'],
            // ['key' => 'specifications', 'value' => 'عيون'],
            // ['key' => 'qualifications', 'value' => ''],
            // ['key' => 'clinic_name', 'value' => ''],
            // ['key' => 'clinic_type', 'value' => ''],
            // ['key' => 'clinic_address', 'value' => ''],
            // ['key' => 'phone', 'value' => ''],
            // ['key' => 'website', 'value' => ''],
            // ['key' => 'email', 'value' => ''],
            // ['key' => 'zoom_api_key','value' => ''],
            // ['key' => 'zoom_api_secret','value' => ''],


            [
                'key' => 'show_ray',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_analysis',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_chronic_diseases',
                'value' =>  1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_glasses_distance',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_prescription',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],

            [
                'key' => 'show_events',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_patients',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_reservations',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_online_reservations',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_medicines',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_num_of_res',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_drugs',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_fees',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_users',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'show_settings',
                'value' => 1,
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'reservation_settings',
                'value' => 'number',
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'reservation_numbers_default',
                'value' => '15',
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'reservation_slots_start_time',
                'value' => '12:00',
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'reservation_slots_end_time',
                'value' => '17:00',
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],
            [
                'key' => 'reservation_slots_interval',
                'value' => '30',
                'type' => 'clinic_reservations_settings',
                'organization_id' => 1,
                'organization_type' => Clinic::class
            ],


        ];

        DB::table('settings')->insert($data);
    }
}
