<?php

namespace Database\Factories;

use App\Models\BudgetHolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BudgetHolderFactory extends Factory
{
    protected $model = BudgetHolder::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'tin' => $this->faker->numerify('##########'),
            'name' => $this->faker->company(),
            'region' => $this->faker->state(),
            'district' => $this->faker->city(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'responsible' => $this->faker->name(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
