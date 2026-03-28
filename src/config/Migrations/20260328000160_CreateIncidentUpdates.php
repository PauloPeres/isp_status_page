<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateIncidentUpdates Migration
 *
 * Creates the incident_updates table for incident timeline/updates system.
 * Team members can post updates about incidents which show on both
 * the admin incident page and the public status page.
 */
class CreateIncidentUpdates extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('incident_updates');

        $table
            ->addColumn('incident_id', 'integer', [
                'null' => false,
                'comment' => 'FK to incidents',
            ])
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'FK to organizations',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'FK to users (null for system-generated updates)',
            ])
            ->addColumn('status', 'string', [
                'limit' => 30,
                'null' => false,
                'comment' => 'Update status: investigating, identified, monitoring, resolved, update',
            ])
            ->addColumn('message', 'text', [
                'null' => false,
                'comment' => 'Update message text',
            ])
            ->addColumn('is_public', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether to show on public status page',
            ])
            ->addColumn('source', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'web',
                'comment' => 'Source of the update: web, api, system, email, telegram, sms',
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Created timestamp',
            ])
            ->addIndex(['incident_id', 'created'], [
                'name' => 'idx_incident_updates_incident_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addIndex(['organization_id', 'created'], [
                'name' => 'idx_incident_updates_org_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addForeignKey('incident_id', 'incidents', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
