<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class BudgetHolderSeeder extends Seeder
{
    public function run()
    {
        $faker = FakerFactory::create('en_US');

        $total = 100000;
        $chunk = 1000;

        for ($i = 0; $i < $total; $i += $chunk) {
            $batch = [];
            $limit = min($chunk, $total - $i);

            for ($j = 0; $j < $limit; $j++) {
                $id = Str::uuid()->toString();
                $now = now();

                $batch[] = [
                    'id' => $id,
                    'tin' => $faker->numerify('##########'),
                    'name' => $faker->company,
                    'region' => $faker->stateAbbr,
                    'district' => $faker->city,
                    'address' => $faker->address,
                    'phone' => $faker->phoneNumber,
                    'responsible' => $faker->name,
                    'created_by' => Str::uuid()->toString(),
                    'updated_by' => Str::uuid()->toString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('budget_holders')->insert($batch);
            unset($batch);
        }
    }
}
