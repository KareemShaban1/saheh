<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $specialties = [
            [
                'name_en' => 'Cardiology',
                'name_ar' => 'طب القلب',
            ],
            [
                'name_en' => 'Dermatology',
                'name_ar' => 'طب الجلدية',
            ],
            [
                'name_en' => 'Endocrinology',
                'name_ar' => 'طب الغدد الصماء',
            ],
            [
                'name_en' => 'Gastroenterology',
                'name_ar' => 'طب الجهاز الهضمي',
            ],
            [
                'name_en' => 'Neurology',
                'name_ar' => 'طب الأعصاب',
            ],
            [
                'name_en' => 'Oncology',
                'name_ar' => 'طب الأورام',
            ],
            [
                'name_en' => 'Pediatrics',
                'name_ar' => 'طب الأطفال',
            ],
            [
                'name_en' => 'Psychiatry',
                'name_ar' => 'الطب النفسي',
            ],
            [
                'name_en' => 'Rheumatology',
                'name_ar' => 'طب الروماتيزم',
            ],
            [
                'name_en' => 'Urology',
                'name_ar' => 'طب المسالك البولية',
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::create($specialty);
        }
    }
}
