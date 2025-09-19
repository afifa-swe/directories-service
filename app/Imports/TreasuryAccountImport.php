<?php

namespace App\Imports;

use App\Models\TreasuryAccount;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;

class TreasuryAccountImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, ShouldQueue
{
    public $queue = 'default';

    public function model(array $row)
    {
        if (empty($row['account']) || empty($row['name'])) {
            Log::warning('TreasuryAccountImport: skipping row, missing required fields', $row);
            return null;
        }

        try {
            return new TreasuryAccount([
                'account' => trim($row['account']),
                'mfo' => $row['mfo'] ?? null,
                'name' => trim($row['name']),
                'department' => $row['department'] ?? null,
                'currency' => $row['currency'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('TreasuryAccountImport: failed to create model', ['error' => $e->getMessage(), 'row' => $row]);
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
