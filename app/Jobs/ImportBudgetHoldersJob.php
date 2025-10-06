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

    protected array $row;
    protected $userId;

    public function __construct(array $row, $userId = null)
    {
        $this->row = $row;
        $this->userId = $userId;

        try {
            Log::info('ImportBudgetHoldersJob created', [
                'row_preview' => array_slice($row, 0, 5),
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            // avoid breaking dispatch
        }
    }

    public function handle()
    {
        // Для демонстрации: задержка, чтобы сообщение висело в очереди RabbitMQ
        sleep(30);

        try {
            Log::info('Start ImportBudgetHoldersJob', ['user_id' => $this->userId]);

            // Map incoming row to model fields. Assume keys are already normalized (lowercase)
            $data = [
                'tin' => $this->row['tin'] ?? null,
                'name' => $this->row['name'] ?? null,
                'region' => $this->row['region'] ?? null,
                'district' => $this->row['district'] ?? null,
                'address' => $this->row['address'] ?? null,
                'phone' => $this->row['phone'] ?? null,
                'responsible' => $this->row['responsible'] ?? null,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ];

            // Minimal validation: require name or tin
            if (empty($data['name']) && empty($data['tin'])) {
                Log::warning('Skipping empty budget holder row', ['row' => $this->row]);
                return;
            }

            BudgetHolder::create($data);

            Log::info('Finished ImportBudgetHoldersJob');
        } catch (\Throwable $e) {
            Log::error('ImportBudgetHoldersJob failed', ['error' => $e->getMessage(), 'row' => $this->row]);
            // optionally rethrow or handle failed job
        }
    }
}
