<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TestQueueJob;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Log;

class TestQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a test job to the queue';

    /**
     * Execute the console command.
     */
    // public function handle(): int
    // {
    //     TestQueueJob::dispatch();

    //     $this->info('📨 TestQueueJob отправлен в очередь.');

    //     return 0;
   // }
   public function handle(): void
{
    sleep(20); // ← задержка перед логированием
    Log::info('🟢 TestQueueJob выполнен с задержкой!');
}

}
