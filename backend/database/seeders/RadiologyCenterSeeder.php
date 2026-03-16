<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RadiologyCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('radiology_centers')->insert([
            'name' => 'Radiology Center 1',
            'address' => 'Radiology Center 1 Address',
            'phone' => '123456789',
            'email' => 'radiology-center1@clinic.com',
            'description' => 'Radiology Center 1 Description',
            'governorate_id' => 1,
            'city_id' => 1,
            'area_id' => 1,
            'website' => 'https://radiology-center1.com',
            "status" => 'approved',
            'is_active' => true,
            'start_date' => Carbon::now(),
        ]);
    }
}
