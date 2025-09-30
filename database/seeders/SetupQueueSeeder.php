<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class SetupQueueSeeder extends Seeder
{
    /**
     * Run the database seeds to setup queues.
     */
    public function run(): void
    {
        // Declare the imports queue in RabbitMQ
        $this->command->info('Running rabbitmq:declare-imports...');
        Artisan::call('rabbitmq:declare-imports');
        $this->command->info('rabbitmq:declare-imports finished');
    }
}
