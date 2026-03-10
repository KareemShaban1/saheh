<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $doctors = [
            ['clinic_id' => 1, 'user_id' => 3, 'phone' => '0123456789', 'certifications' => 'certifications 3'],
            ['clinic_id' => 1, 'user_id' => 4, 'phone' => '0123456789', 'certifications' => 'certifications 4'],
            ['clinic_id' => 2, 'user_id' => 7, 'phone' => '0123456789', 'certifications' => 'certifications 7'],
            ['clinic_id' => 2, 'user_id' => 8, 'phone' => '0123456789', 'certifications' => 'certifications 8'],
                    
        ];
        
        foreach($doctors as $doctor){
            DB::table('doctors')->insert($doctor);
        }
       
    }
}
