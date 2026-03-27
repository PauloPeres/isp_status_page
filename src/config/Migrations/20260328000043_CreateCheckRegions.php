<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateCheckRegions Migration
 *
 * Creates the check_regions table for multi-region monitoring architecture.
 */
class CreateCheckRegions extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('check_regions');

        $table
            ->addColumn('name', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Human-readable region name',
            ])
            ->addColumn('code', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Unique region code (e.g. us-east-1)',
            ])
            ->addColumn('endpoint_url', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
                'comment' => 'URL of the regional check worker endpoint',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this region is active',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'comment' => 'Creation timestamp',
            ])
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['active'])
            ->create();
    }
}
