<?php

namespace Database\Factories;

use App\Models\Clinic;
use Modules\Clinic\Doctor\Models\Doctor;
use App\Models\Specialty;
use Modules\Clinic\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Clinic\Doctor\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'clinic_id' => Clinic::factory(),
            'phone' => $this->faker->numerify('###-###-####'),
            'certifications' => $this->faker->randomElement(['MBBS', 'MD', 'MS', 'DNB', 'DM', 'MCh']),
            'specialty_id' => specialty::factory(),
        ];
    }
}
