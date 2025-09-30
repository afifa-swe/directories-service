<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Faker\Factory as FakerFactory;

class GenerateMassData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:mass-data {--perUser=100000} {--batch=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate large amounts of test data (swift_codes, budget_holders, treasury_accounts) per user.';

    public function handle()
    {
        $perUser = (int) $this->option('perUser');
        $batch = (int) $this->option('batch');

        $faker = FakerFactory::create();

        $users = User::all();
        if ($users->isEmpty()) {
            $this->error('No users found in users table. Create users first.');
            return 1;
        }

        $this->info("Generating {$perUser} records per user (batch={$batch})...");

        foreach ($users as $user) {
            $this->info("Processing user {$user->id}...");

            // Swift codes
            $this->generateInBatches('swift_codes', $perUser, $batch, function() use ($faker, $user) {
                return [
                    'id' => (string) Str::uuid(),
                    'swift_code' => strtoupper($faker->bothify('??????')),
                    'bank_name' => $faker->company(),
                    'country' => $faker->country(),
                    'city' => $faker->city(),
                    'address' => $faker->address(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            // Budget holders
            $this->generateInBatches('budget_holders', $perUser, $batch, function() use ($faker, $user) {
                return [
                    'id' => (string) Str::uuid(),
                    'tin' => $faker->numerify('##########'),
                    'name' => $faker->company(),
                    'region' => $faker->state(),
                    'district' => $faker->city(),
                    'address' => $faker->address(),
                    'phone' => $faker->phoneNumber(),
                    'responsible' => $faker->name(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            // Treasury accounts
            $this->generateInBatches('treasury_accounts', $perUser, $batch, function() use ($faker, $user) {
                return [
                    'id' => (string) Str::uuid(),
                    'account' => $faker->bankAccountNumber(),
                    'mfo' => $faker->numerify('######'),
                    'name' => $faker->company(),
                    'department' => $faker->word(),
                    'currency' => $faker->currencyCode(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            $this->info("Done for user {$user->id}.");
        }

        $this->info('All done.');
        return 0;
    }

    protected function generateInBatches(string $table, int $total, int $batchSize, callable $rowFactory)
    {
        $inserted = 0;
        while ($inserted < $total) {
            $toInsert = min($batchSize, $total - $inserted);
            $rows = [];
            for ($i = 0; $i < $toInsert; $i++) {
                $rows[] = $rowFactory();
            }

            DB::table($table)->insert($rows);
            $inserted += $toInsert;
            $this->info("  Inserted {$inserted}/{$total} into {$table}...");
        }
    }
}
