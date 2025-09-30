<?php

namespace App\Imports;

use App\Models\TreasuryAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Row;

class TreasuryAccountImport implements OnEachRow, WithChunkReading, WithHeadingRow, ShouldQueue
{
    public $queue = 'imports';

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        Log::info('TreasuryAccountImport: raw row', $row);

        // Normalize keys
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        Log::info('TreasuryAccountImport: normalized row', $normalized);

        if (empty($normalized['account']) || empty($normalized['name'])) {
            Log::warning('TreasuryAccountImport: skipping row, missing required fields', $normalized);
            return;
        }

        try {
            $model = TreasuryAccount::create([
                'id' => (string) Str::uuid(),
                'account' => trim($normalized['account']),
                'mfo' => isset($normalized['mfo']) ? trim($normalized['mfo']) : null,
                'name' => trim($normalized['name']),
                'department' => isset($normalized['department']) ? trim($normalized['department']) : null,
                'currency' => isset($normalized['currency']) ? trim($normalized['currency']) : null,
            ]);

            Log::info('TreasuryAccountImport: created model instance', ['account' => $model->account, 'id' => $model->id]);
        } catch (\Throwable $e) {
            Log::error('TreasuryAccountImport: failed to create model', ['error' => $e->getMessage(), 'row' => $normalized]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
