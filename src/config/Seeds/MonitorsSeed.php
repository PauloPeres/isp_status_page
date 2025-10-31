<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Monitors seed.
 *
 * Creates example monitors for testing and demonstration.
 */
class MonitorsSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            // Example HTTP Monitor - Google
            [
                'name' => 'Google DNS',
                'description' => 'Monitor Google DNS service availability',
                'type' => 'http',
                'configuration' => json_encode([
                    'url' => 'https://dns.google',
                    'method' => 'GET',
                    'expected_status' => [200],
                    'ssl_verify' => true,
                ]),
                'check_interval' => 60,
                'timeout' => 10,
                'retry_count' => 3,
                'status' => 'unknown',
                'last_check_at' => null,
                'next_check_at' => $now,
                'uptime_percentage' => null,
                'active' => 1,
                'visible_on_status_page' => 1,
                'display_order' => 1,
                'created' => $now,
                'modified' => $now,
            ],

            // Example Ping Monitor
            [
                'name' => 'Google Public DNS (8.8.8.8)',
                'description' => 'Ping test to Google DNS server',
                'type' => 'ping',
                'configuration' => json_encode([
                    'host' => '8.8.8.8',
                    'packet_count' => 4,
                    'max_latency' => 100,
                ]),
                'check_interval' => 60,
                'timeout' => 10,
                'retry_count' => 3,
                'status' => 'unknown',
                'last_check_at' => null,
                'next_check_at' => $now,
                'uptime_percentage' => null,
                'active' => 1,
                'visible_on_status_page' => 1,
                'display_order' => 2,
                'created' => $now,
                'modified' => $now,
            ],

            // Example Port Monitor
            [
                'name' => 'Google HTTPS Port',
                'description' => 'Check if Google HTTPS port is open',
                'type' => 'port',
                'configuration' => json_encode([
                    'host' => 'google.com',
                    'port' => 443,
                    'protocol' => 'tcp',
                ]),
                'check_interval' => 120,
                'timeout' => 10,
                'retry_count' => 3,
                'status' => 'unknown',
                'last_check_at' => null,
                'next_check_at' => $now,
                'uptime_percentage' => null,
                'active' => 1,
                'visible_on_status_page' => 1,
                'display_order' => 3,
                'created' => $now,
                'modified' => $now,
            ],

            // Example API Monitor
            [
                'name' => 'JSONPlaceholder API',
                'description' => 'Test API monitoring with public JSON API',
                'type' => 'api',
                'configuration' => json_encode([
                    'url' => 'https://jsonplaceholder.typicode.com/posts/1',
                    'method' => 'GET',
                    'expected_status' => [200],
                    'json_path' => 'userId',
                    'expected_value' => '1',
                ]),
                'check_interval' => 300,
                'timeout' => 15,
                'retry_count' => 2,
                'status' => 'unknown',
                'last_check_at' => null,
                'next_check_at' => $now,
                'uptime_percentage' => null,
                'active' => 0, // Disabled by default
                'visible_on_status_page' => 0,
                'display_order' => 4,
                'created' => $now,
                'modified' => $now,
            ],
        ];

        $table = $this->table('monitors');
        $table->insert($data)->save();
    }
}
