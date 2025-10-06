<?php

namespace App\Jobs;

use App\Models\SwiftCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportSwiftCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $chunk;
    protected $userId;

    // Do not declare public $queue or public $connection here to avoid collisions
    // with the Queueable trait. The controller dispatches the job with
    // ->onConnection('rabbitmq')->onQueue('imports').

    public function __construct(array $chunk, $userId = null)
    {
        $this->chunk = $chunk;
        $this->userId = $userId;
        // Log at construction time so we can see the job was created/dispatched
        try {
            Log::info('ImportSwiftCodesJob created', [
                'rows' => is_countable($chunk) ? count($chunk) : null,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            // Avoid breaking dispatch if logging fails for some reason
        }
    }

    public function handle()
    {
        // Log payload (careful: may be large) and start
        try {
            Log::debug('ImportSwiftCodesJob payload', ['chunk' => $this->chunk]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        Log::info('Start SwiftCode chunk', ['rows' => is_countable($this->chunk) ? count($this->chunk) : null, 'user_id' => $this->userId]);

        foreach ($this->chunk as $row) {
            if (empty($row['swift_code']) || empty($row['bank_name'])) {
                continue;
            }

            SwiftCode::create([
                'id' => (string) Str::uuid(),
                'swift_code' => trim($row['swift_code']),
                'bank_name' => trim($row['bank_name']),
                'country' => $row['country'] ?? null,
                'city' => $row['city'] ?? null,
                'address' => $row['address'] ?? null,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        }

        Log::info('Finished SwiftCode chunk');
    }
}
