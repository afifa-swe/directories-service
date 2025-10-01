<?php

namespace App\Imports;

use App\Models\BudgetHolder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Row;

class BudgetHolderImport implements OnEachRow, WithChunkReading, WithHeadingRow, ShouldQueue
{
    public $queue = 'imports';
    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        Log::info('BudgetHolderImport: raw row', $row);

        // Normalize keys
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        Log::info('BudgetHolderImport: normalized row', $normalized);

        if (empty($normalized['tin']) || empty($normalized['name'])) {
            Log::warning('BudgetHolderImport: skipping row, missing required fields', $normalized);
            return;
        }

        try {
            $model = BudgetHolder::create([
                'id' => (string) Str::uuid(),
                'tin' => trim($normalized['tin']),
                'name' => trim($normalized['name']),
                'region' => isset($normalized['region']) ? trim($normalized['region']) : null,
                'district' => isset($normalized['district']) ? trim($normalized['district']) : null,
                'address' => isset($normalized['address']) ? trim($normalized['address']) : null,
                'phone' => isset($normalized['phone']) ? trim($normalized['phone']) : null,
                'responsible' => isset($normalized['responsible']) ? trim($normalized['responsible']) : null,
                'created_by' => $this->userId ?? null,
                'updated_by' => $this->userId ?? null,
            ]);

            Log::info('BudgetHolderImport: created model instance', ['tin' => $model->tin, 'id' => $model->id]);
        } catch (\Throwable $e) {
            Log::error('BudgetHolderImport: failed to create model', ['error' => $e->getMessage(), 'row' => $normalized]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
