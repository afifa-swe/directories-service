<?php

namespace App\Jobs;

use App\Models\BudgetHolder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ImportBudgetHoldersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected ?array $row = null;
    protected array $chunk = [];
    protected $userId = null;

    /**
     * Ensure properties are initialized after unserialization (old payloads may miss typed defaults)
     * This avoids "Typed property ... must not be accessed before initialization" errors.
     */
    public function __wakeup()
    {
        if (!isset($this->chunk) || !is_array($this->chunk)) {
            $this->chunk = [];
        }

        if (!isset($this->row)) {
            $this->row = null;
        }

        if (!isset($this->userId)) {
            $this->userId = null;
        }
    }

    public function __construct(array $chunk, $userId = null)
    {
        $this->chunk = $chunk;
        $this->userId = $userId;

        try {
            Log::info('ImportBudgetHoldersJob created', [
                'rows' => is_countable($chunk) ? count($chunk) : null,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            // avoid breaking dispatch
        }
    }

    public function handle()
    {
        Log::info('Start ImportBudgetHoldersJob chunk', ['rows' => is_countable($this->chunk) ? count($this->chunk) : null, 'user_id' => $this->userId]);
        // write a small file marker to indicate job started (helpful when queue runs in separate container)
        try {
            file_put_contents(storage_path('logs/import-started.log'), now()->toIso8601String() . ' - started - rows: ' . (is_countable($this->chunk) ? count($this->chunk) : 'n/a') . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore
        }

        foreach ($this->chunk as $row) {
            try {
                // Fill NOT NULL columns with safe defaults (empty string) when missing
                $data = [
                    // accept both 'tin' and legacy 'inn' column names
                    'tin' => $row['tin'] ?? ($row['inn'] ?? null),
                    // name and migration columns are NOT NULL; use empty string when absent
                    'name' => $row['name'] ?? '',
                    'region' => $row['region'] ?? '',
                    'district' => $row['district'] ?? '',
                    'address' => $row['address'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'responsible' => $row['responsible'] ?? '',
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ];

                if (empty($data['name']) && empty($data['tin'])) {
                    $this->logProblematicRow('empty_name_and_tin', $row);
                    Log::warning('Skipping empty budget holder row', ['row' => $row]);
                    continue;
                }

                BudgetHolder::create($data);

                // short pause between rows to show activity and avoid bursts
                sleep(rand(2, 5));
            } catch (\Throwable $e) {
                Log::error('Failed to import row in chunk', ['error' => $e->getMessage(), 'row' => $row]);
                $this->logProblematicRow($e->getMessage(), $row);
                continue;
            }
        }

        Log::info('Finished ImportBudgetHoldersJob chunk', ['rows' => is_countable($this->chunk) ? count($this->chunk) : null]);
        try {
            file_put_contents(storage_path('logs/import-finished.log'), now()->toIso8601String() . ' - finished - rows: ' . (is_countable($this->chunk) ? count($this->chunk) : 'n/a') . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    protected function logProblematicRow($reason, array $row)
    {
        try {
            $path = storage_path('logs/problematic-budget-holders.log');
            $entry = json_encode(['time' => now()->toIso8601String(), 'reason' => $reason, 'row' => $row], JSON_UNESCAPED_UNICODE);
            file_put_contents($path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // swallow
        }
    }
}
