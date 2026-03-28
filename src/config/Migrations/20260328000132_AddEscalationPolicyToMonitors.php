<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddEscalationPolicyToMonitors Migration
 *
 * Adds escalation_policy_id foreign key to monitors table,
 * allowing each monitor to optionally use an escalation policy.
 */
class AddEscalationPolicyToMonitors extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitors');

        $table
            ->addColumn('escalation_policy_id', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'organization_id',
                'comment' => 'Optional escalation policy for this monitor',
            ])
            ->addIndex(['escalation_policy_id'], [
                'name' => 'idx_monitors_escalation_policy',
            ])
            ->addForeignKey('escalation_policy_id', 'escalation_policies', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_monitors_escalation_policy',
            ])
            ->update();
    }
}
