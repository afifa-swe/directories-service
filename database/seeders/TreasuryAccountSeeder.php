<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class TreasuryAccountSeeder extends Seeder
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
                    'account' => $faker->bankAccountNumber(),
                    'mfo' => $faker->numerify('######'),
                    'name' => $faker->company,
                    'department' => $faker->companySuffix,
                    'currency' => $faker->currencyCode,
                    'created_by' => Str::uuid()->toString(),
                    'updated_by' => Str::uuid()->toString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('treasury_accounts')->insert($batch);
            unset($batch);
        }
    }
}
