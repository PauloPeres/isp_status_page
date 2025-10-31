<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSettings Migration
 *
 * Creates the settings table for application configuration.
 * Stores key-value pairs for system settings.
 */
class CreateSettings extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('settings');

        $table
            ->addColumn('key', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Setting key (unique identifier)'
            ])
            ->addColumn('value', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Setting value'
            ])
            ->addColumn('type', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'string',
                'comment' => 'Value type: string, integer, boolean, json'
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Setting description'
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp'
            ])
            ->addIndex(['key'], [
                'unique' => true,
                'name' => 'idx_settings_key'
            ])
            ->create();
    }
}
