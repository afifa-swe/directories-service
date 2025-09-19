<?php

namespace Database\Seeders;

use App\Models\TreasuryAccount;
use Illuminate\Database\Seeder;

class TreasuryAccountSeeder extends Seeder
{
    public function run()
    {
        $total = 110000;
        $batch = 2000;

        for ($i = 0; $i < $total; $i += $batch) {
            $count = min($batch, $total - $i);
            TreasuryAccount::factory()->count($count)->create();
            if ($this->command) {
                $this->command->info("TreasuryAccount seeded " . ($i + $count) . " / $total");
            }
        }
    }
}
