<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class SwiftCodeSeeder extends Seeder
{
    public function run()
    {
        $faker = FakerFactory::create('en_US');

        $total = 100000; // number of records to create
        $chunk = 1000; // insert chunk size

        for ($i = 0; $i < $total; $i += $chunk) {
            $batch = [];
            $limit = min($chunk, $total - $i);

            for ($j = 0; $j < $limit; $j++) {
                $id = Str::uuid()->toString();
                $now = now();

                $batch[] = [
                    'id' => $id,
                    'swift_code' => strtoupper($faker->bothify('????' . $faker->numberBetween(100, 999))),
                    'bank_name' => $faker->company,
                    'country' => $faker->country,
                    'city' => $faker->city,
                    'address' => $faker->address,
                    'created_by' => Str::uuid()->toString(),
                    'updated_by' => Str::uuid()->toString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('swift_codes')->insert($batch);
            // free memory
            unset($batch);
        }
    }
}
