<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\City;
use App\Models\Governorate;
use App\Models\MedicalLaboratory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalLaboratory>
 */
class MedicalLaboratoryFactory extends Factory
{
    protected $model = MedicalLaboratory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => 'Medical Lab ' . $this->faker->company(),
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'address' => $this->faker->address(),
            'phone' => $this->faker->numerify('###-###-####'),
            'email' => $this->faker->unique()->safeEmail(),
            'website' => $this->faker->url(),
            'description' => $this->faker->paragraph(),
            'status' => 1,
            'governorate_id' => Governorate::factory(),
            'city_id' => City::factory(),
            'area_id' => Area::factory(),
        ];
    }
}
