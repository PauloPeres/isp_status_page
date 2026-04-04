<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSipConfigurations Migration
 *
 * Creates the sip_configurations table for custom SIP trunk settings per organization.
 */
class CreateSipConfigurations extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('sip_configurations');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('provider', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'keepup_default',
            ])
            ->addColumn('sip_host', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('sip_port', 'integer', [
                'null' => true,
                'default' => 5060,
            ])
            ->addColumn('sip_username', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('sip_password', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Stored encrypted',
            ])
            ->addColumn('sip_transport', 'string', [
                'limit' => 10,
                'null' => true,
                'default' => 'udp',
            ])
            ->addColumn('caller_id', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'comment' => 'Outbound caller ID in E.164 format',
            ])
            ->addColumn('twilio_trunk_sid', 'string', [
                'limit' => 64,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
            ])
            ->addColumn('last_tested_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('last_test_result', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('public_id', 'string', [
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['organization_id'], [
                'unique' => true,
                'name' => 'idx_sip_configurations_org',
            ])
            ->addIndex(['public_id'], [
                'unique' => true,
                'name' => 'idx_sip_configurations_public_id',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_sip_configurations_organization',
            ])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('sip_configurations')->drop()->save();
    }
}
