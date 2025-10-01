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
        // Accept common CSV header variants: 'inn' -> 'tin'
        $tin = isset($normalized['tin']) ? trim($normalized['tin']) : (isset($normalized['inn']) ? trim($normalized['inn']) : null);
        $name = isset($normalized['name']) ? trim($normalized['name']) : null;

        if (empty($tin) || empty($name)) {
            Log::warning('BudgetHolderImport: skipping row, missing required fields', $normalized);
            return;
        }

        try {
            // Use the normalized variables we validated above and provide safe defaults
            $model = BudgetHolder::create([
                'id' => (string) Str::uuid(),
                'tin' => $tin,
                'name' => $name,
                // These columns are non-nullable in the migration; provide empty string defaults
                'region' => isset($normalized['region']) && strlen(trim($normalized['region'])) ? trim($normalized['region']) : '',
                'district' => isset($normalized['district']) && strlen(trim($normalized['district'])) ? trim($normalized['district']) : '',
                'address' => isset($normalized['address']) && strlen(trim($normalized['address'])) ? trim($normalized['address']) : '',
                'phone' => isset($normalized['phone']) && strlen(trim($normalized['phone'])) ? trim($normalized['phone']) : '',
                'responsible' => isset($normalized['responsible']) && strlen(trim($normalized['responsible'])) ? trim($normalized['responsible']) : '',
                'created_by' => $this->userId ?? null,
                'updated_by' => $this->userId ?? null,
            ]);

            Log::info('BudgetHolderImport: created model instance', ['tin' => $model->tin, 'id' => $model->id, 'created_by' => $model->created_by]);
        } catch (\Throwable $e) {
            Log::error('BudgetHolderImport: failed to create model', ['error' => $e->getMessage(), 'row' => $normalized]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
