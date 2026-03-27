<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateWebhookDeliveries Migration
 *
 * Creates the webhook_deliveries table for tracking outbound webhook delivery attempts.
 */
class CreateWebhookDeliveries extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('webhook_deliveries');

        $table
            ->addColumn('webhook_endpoint_id', 'integer', [
                'null' => false,
                'comment' => 'Webhook endpoint this delivery belongs to',
            ])
            ->addColumn('event_type', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Event type (e.g. monitor.down, incident.created)',
            ])
            ->addColumn('payload', 'text', [
                'null' => false,
                'comment' => 'JSON payload sent to the endpoint',
            ])
            ->addColumn('response_code', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'HTTP response status code',
            ])
            ->addColumn('response_body', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'HTTP response body',
            ])
            ->addColumn('attempts', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of delivery attempts',
            ])
            ->addColumn('delivered_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Timestamp when successfully delivered',
            ])
            ->addColumn('next_retry_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Timestamp for next retry attempt',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'comment' => 'Creation timestamp',
            ])
            ->addIndex(['webhook_endpoint_id'])
            ->addIndex(['event_type'])
            ->addIndex(['next_retry_at'])
            ->addForeignKey('webhook_endpoint_id', 'webhook_endpoints', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
