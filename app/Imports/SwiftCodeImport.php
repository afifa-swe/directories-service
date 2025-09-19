<?php

namespace App\Imports;

use App\Models\SwiftCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Row;

class SwiftCodeImport implements OnEachRow, WithChunkReading, WithHeadingRow, ShouldQueue
{
    public $queue = 'default';

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        // Log raw row for debugging
        Log::info('SwiftCodeImport: raw row', $row);

        // Normalize keys to lower-case
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        Log::info('SwiftCodeImport: normalized row', $normalized);

        if (empty($normalized['swift_code']) || empty($normalized['bank_name'])) {
            Log::warning('SwiftCodeImport: skipping row, missing required fields', $normalized);
            return;
        }

        try {
            $model = SwiftCode::create([
                'id' => (string) Str::uuid(),
                'swift_code' => trim($normalized['swift_code']),
                'bank_name' => trim($normalized['bank_name']),
                'country' => isset($normalized['country']) ? trim($normalized['country']) : null,
                'city' => isset($normalized['city']) ? trim($normalized['city']) : null,
                'address' => isset($normalized['address']) ? trim($normalized['address']) : null,
            ]);

            Log::info('SwiftCodeImport: created model instance', ['swift_code' => $model->swift_code, 'id' => $model->id]);
        } catch (\Throwable $e) {
            Log::error('SwiftCodeImport: failed to create model', ['error' => $e->getMessage(), 'row' => $normalized]);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
