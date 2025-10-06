<?php

namespace App\Jobs;

use App\Models\TreasuryAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportTreasuryAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // job handles one row per job
    protected array $row;
    protected $userId;

    // queue name is set on dispatch; avoid defining $queue property here to prevent trait conflicts

    public function __construct(array $row, $userId = null)
    {
        $this->row = $row;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            // normalize keys to lowercase
            $normalized = [];
            foreach ($this->row as $k => $v) {
                $normalized[mb_strtolower(trim($k))] = is_string($v) ? trim($v) : $v;
            }

            // Accept header variants and fallbacks
            $account = $normalized['account'] ?? ($normalized['account_number'] ?? ($normalized['account_no'] ?? null));
            $name = $normalized['name'] ?? ($normalized['account_name'] ?? null);
            $mfo = $normalized['mfo'] ?? ($normalized['bank_code'] ?? ($normalized['bank'] ?? ''));
            $department = $normalized['department'] ?? ($normalized['dept'] ?? '');
            $currency = $normalized['currency'] ?? ($normalized['cur'] ?? '');

            if (empty($account) || empty($name)) {
                $this->logProblematicRow('missing_required_fields', $normalized);
                Log::warning('ImportTreasuryAccountsJob: missing account or name', ['row' => $normalized]);
                return;
            }

            TreasuryAccount::create([
                'account' => $account,
                'mfo' => strlen((string)$mfo) ? $mfo : '',
                'name' => $name,
                'department' => strlen((string)$department) ? $department : '',
                'currency' => strlen((string)$currency) ? $currency : '',
                'created_by' => $this->userId ?? null,
                'updated_by' => $this->userId ?? null,
            ]);

            Log::info('ImportTreasuryAccountsJob: created treasury account', ['account' => $account]);
        } catch (\Throwable $e) {
            Log::error('ImportTreasuryAccountsJob: failed to import row', ['error' => $e->getMessage(), 'row' => $this->row]);
            $this->logProblematicRow($e->getMessage(), $this->row);
        }
    }

    protected function logProblematicRow($reason, array $row)
    {
        try {
            $path = storage_path('logs/problematic-treasury-accounts.log');
            $entry = json_encode(['time' => now()->toIso8601String(), 'reason' => $reason, 'row' => $row], JSON_UNESCAPED_UNICODE);
            file_put_contents($path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // swallow
        }
    }
}
