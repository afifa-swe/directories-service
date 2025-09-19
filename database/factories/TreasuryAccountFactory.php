<?php

namespace Database\Factories;

use App\Models\TreasuryAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TreasuryAccountFactory extends Factory
{
    protected $model = TreasuryAccount::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'account' => $this->faker->bankAccountNumber(),
            'mfo' => $this->faker->numerify('######'),
            'name' => $this->faker->company(),
            'department' => $this->faker->word(),
            'currency' => $this->faker->currencyCode(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
