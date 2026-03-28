<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateEscalationPolicies Migration
 *
 * Creates the escalation_policies table for alert escalation workflows.
 * Each policy belongs to an organization and defines a series of escalation steps.
 */
class CreateEscalationPolicies extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('escalation_policies');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this escalation policy',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Human-readable policy name',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Optional description of the escalation policy',
            ])
            ->addColumn('repeat_enabled', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Whether to repeat escalation cycle after completing all steps',
            ])
            ->addColumn('repeat_after_minutes', 'integer', [
                'null' => false,
                'default' => 60,
                'comment' => 'Minutes to wait before repeating escalation cycle',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this policy is active',
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
            ->addIndex(['organization_id'], [
                'name' => 'idx_escalation_policies_org',
            ])
            ->addIndex(['active'], [
                'name' => 'idx_escalation_policies_active',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_escalation_policies_organization',
            ])
            ->create();
    }
}
