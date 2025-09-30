<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class DeclareImportsQueue extends Command
{
    protected $signature = 'rabbitmq:declare-imports';
    protected $description = 'Declare the imports queue and exchange in RabbitMQ';

    public function handle()
    {
        /**
         * This command creates the 'imports' queue and binds it to the configured exchange.
         *
         * When to call:
         * - Manually before the first run in a new environment (local/CI).
         * - As part of deployment scripts (docker-entrypoint or custom deploy script).
         * - As part of database seeding: DatabaseSeeder now runs SetupQueueSeeder which calls this command.
         *
         * Note: This is idempotent - declaring an already existing queue is safe.
         * See README.md for details.
         */
        $this->info('Declaring imports queue...');

        try {
            $manager = app('queue');
            /** @var \VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue $connection */
            $connection = $manager->connection('rabbitmq');

            // Declare queue 'imports'
            $connection->declareQueue('imports', true, false, []);

            // If an exchange is configured, declare and bind
            $exchangeName = config('queue.connections.rabbitmq.options.exchange.name');
            if (! empty($exchangeName)) {
                $connection->declareExchange($exchangeName, config('queue.connections.rabbitmq.options.exchange.type', 'direct'));
                $connection->bindQueue('imports', $exchangeName, 'imports');
            }

            $this->info("Declared queue 'imports'" . ($exchangeName ? " and bound to exchange: {$exchangeName}" : ''));
            Log::info('Declared imports queue', ['exchange' => $exchangeName]);
        } catch (\Throwable $e) {
            $this->error('Failed to declare imports queue: ' . $e->getMessage());
            Log::error('rabbitmq:declare-imports failed', ['error' => $e->getMessage()]);
            return 1;
        }

        return 0;
    }
}
