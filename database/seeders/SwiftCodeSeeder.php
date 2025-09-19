<?php

namespace Database\Seeders;

use App\Models\SwiftCode;
use Illuminate\Database\Seeder;

class SwiftCodeSeeder extends Seeder
{
    public function run()
    {
        $total = 100000;
        $batch = 2000; // insert in batches to reduce memory usage

        for ($i = 0; $i < $total; $i += $batch) {
            $count = min($batch, $total - $i);
            SwiftCode::factory()->count($count)->create();
            if ($this->command) {
                $this->command->info("SwiftCode seeded " . ($i + $count) . " / $total");
            }
        }
    }
}
