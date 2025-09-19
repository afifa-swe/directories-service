<?php

namespace App\Imports;

use App\Models\BudgetHolder;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;

class BudgetHolderImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, ShouldQueue
{
    public $queue = 'default';

    public function model(array $row)
    {
        if (empty($row['tin']) || empty($row['name'])) {
            Log::warning('BudgetHolderImport: skipping row, missing required fields', $row);
            return null;
        }

        try {
            return new BudgetHolder([
                'tin' => trim($row['tin']),
                'name' => trim($row['name']),
                'region' => $row['region'] ?? null,
                'district' => $row['district'] ?? null,
                'address' => $row['address'] ?? null,
                'phone' => $row['phone'] ?? null,
                'responsible' => $row['responsible'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('BudgetHolderImport: failed to create model', ['error' => $e->getMessage(), 'row' => $row]);
            return null;
        }
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
