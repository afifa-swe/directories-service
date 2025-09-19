<?php

namespace Database\Factories;

use App\Models\SwiftCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SwiftCodeFactory extends Factory
{
    protected $model = SwiftCode::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'swift_code' => strtoupper($this->faker->bothify('??????')),
            'bank_name' => $this->faker->company(),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
