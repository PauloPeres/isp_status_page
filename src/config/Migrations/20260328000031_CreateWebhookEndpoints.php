<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateWebhookEndpoints Migration
 *
 * Creates the webhook_endpoints table for storing outbound webhook configurations.
 */
class CreateWebhookEndpoints extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('webhook_endpoints');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this webhook endpoint belongs to',
            ])
            ->addColumn('url', 'string', [
                'limit' => 2048,
                'null' => false,
                'comment' => 'Webhook delivery URL',
            ])
            ->addColumn('secret', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'HMAC-SHA256 signing secret',
            ])
            ->addColumn('events', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array of subscribed event types',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this endpoint is active',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'comment' => 'Creation timestamp',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'comment' => 'Last modification timestamp',
            ])
            ->addIndex(['organization_id'])
            ->addIndex(['active'])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
