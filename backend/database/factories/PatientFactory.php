<?php

namespace Database\Factories;

use App\Models\Shared\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(1, 90),
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'), // Default password for all patients
            'phone' => $this->faker->unique()->numerify('###-###-####'),
            'whatsapp_number' => $this->faker->numerify('###-###-####'),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'height' => $this->faker->numberBetween(150, 200),
            'weight' => $this->faker->numberBetween(40, 120),
            'marital_status' => $this->faker->randomElement(['single', 'married', 'widowed', 'divorced', 'separated']),
            'active' => true,
        ];
    }
}