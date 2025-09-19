<?php

namespace Database\Seeders;

use App\Models\BudgetHolder;
use Illuminate\Database\Seeder;

class BudgetHolderSeeder extends Seeder
{
    public function run()
    {
        $total = 120000;
        $batch = 2000;

        for ($i = 0; $i < $total; $i += $batch) {
            $count = min($batch, $total - $i);
            BudgetHolder::factory()->count($count)->create();
            if ($this->command) {
                $this->command->info("BudgetHolder seeded " . ($i + $count) . " / $total");
            }
        }
    }
}
