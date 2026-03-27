<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateStatusPages Migration
 *
 * Creates the status_pages table for custom per-organization status pages.
 */
class CreateStatusPages extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('status_pages');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this status page belongs to',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Status page name',
            ])
            ->addColumn('slug', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'URL slug for the status page',
            ])
            ->addColumn('custom_domain', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Custom domain for the status page',
            ])
            ->addColumn('theme', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Theme configuration (JSON)',
            ])
            ->addColumn('header_text', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Custom header text/HTML',
            ])
            ->addColumn('footer_text', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Custom footer text/HTML',
            ])
            ->addColumn('monitors', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array of monitor IDs to display',
            ])
            ->addColumn('show_uptime_chart', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether to show uptime chart',
            ])
            ->addColumn('show_incident_history', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether to show incident history',
            ])
            ->addColumn('password', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Optional password protection',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this status page is active',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_status_pages_organization_id',
            ])
            ->addIndex(['slug'], [
                'unique' => true,
                'name' => 'idx_status_pages_slug_unique',
            ])
            ->addIndex(['organization_id'], [
                'name' => 'idx_status_pages_organization_id',
            ])
            ->addIndex(['custom_domain'], [
                'name' => 'idx_status_pages_custom_domain',
            ])
            ->addIndex(['active'], [
                'name' => 'idx_status_pages_active',
            ])
            ->create();
    }
}
