<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateEscalationSteps Migration
 *
 * Creates the escalation_steps table for defining individual steps
 * within an escalation policy (e.g., email at 0 min, SMS at 5 min, etc.).
 */
class CreateEscalationSteps extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('escalation_steps');

        $table
            ->addColumn('escalation_policy_id', 'integer', [
                'null' => false,
                'comment' => 'Parent escalation policy',
            ])
            ->addColumn('step_number', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Step order number (1 = first step)',
            ])
            ->addColumn('wait_minutes', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Minutes to wait from incident start before executing this step',
            ])
            ->addColumn('channel', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Alert channel: email, slack, discord, telegram, webhook, sms',
            ])
            ->addColumn('recipients', 'text', [
                'null' => false,
                'comment' => 'JSON array of recipients for this step',
            ])
            ->addColumn('message_template', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Optional custom message template for this step',
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
            ->addIndex(['escalation_policy_id'], [
                'name' => 'idx_escalation_steps_policy',
            ])
            ->addIndex(['escalation_policy_id', 'step_number'], [
                'name' => 'idx_escalation_steps_policy_step',
                'unique' => true,
            ])
            ->addForeignKey('escalation_policy_id', 'escalation_policies', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_escalation_steps_policy',
            ])
            ->create();
    }
}
