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
    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

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
        $account = isset($normalized['account']) ? trim($normalized['account']) : (isset($normalized['account_number']) ? trim($normalized['account_number']) : null);
        $name = isset($normalized['name']) ? trim($normalized['name']) : (isset($normalized['account_name']) ? trim($normalized['account_name']) : null);

        if (empty($account) || empty($name)) {
            Log::warning('TreasuryAccountImport: skipping row, missing required fields', $normalized);
            return;
        }

        try {
            $model = TreasuryAccount::create([
                'id' => (string) Str::uuid(),
                'account' => $account,
                // mfo is non-nullable in migration; default to empty string if missing
                'mfo' => (isset($normalized['mfo']) && strlen(trim($normalized['mfo']))) ? trim($normalized['mfo']) : (isset($normalized['bank_code']) && strlen(trim($normalized['bank_code'])) ? trim($normalized['bank_code']) : ''),
                'name' => $name,
                'department' => isset($normalized['department']) && strlen(trim($normalized['department'])) ? trim($normalized['department']) : '',
                'currency' => isset($normalized['currency']) && strlen(trim($normalized['currency'])) ? trim($normalized['currency']) : '',
                'created_by' => $this->userId ?? null,
                'updated_by' => $this->userId ?? null,
            ]);

            Log::info('TreasuryAccountImport: created model instance', ['account' => $model->account, 'id' => $model->id, 'created_by' => $model->created_by]);
        } catch (\Throwable $e) {
            Log::error('TreasuryAccountImport: failed to create model', ['error' => $e->getMessage(), 'row' => $normalized]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
