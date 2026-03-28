<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateNotificationCredits Migration
 *
 * Creates the notification_credits table for tracking organization credit balances
 * used for paid notification channels (SMS, WhatsApp).
 */
class CreateNotificationCredits extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('notification_credits');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this credit balance',
            ])
            ->addColumn('balance', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Current credit balance',
            ])
            ->addColumn('monthly_grant', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of credits granted monthly based on plan',
            ])
            ->addColumn('auto_recharge', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Whether to auto-purchase credits when balance is low',
            ])
            ->addColumn('auto_recharge_threshold', 'integer', [
                'null' => false,
                'default' => 10,
                'comment' => 'Balance threshold that triggers auto-recharge',
            ])
            ->addColumn('auto_recharge_amount', 'integer', [
                'null' => false,
                'default' => 100,
                'comment' => 'Number of credits to purchase on auto-recharge',
            ])
            ->addColumn('last_grant_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Timestamp of last monthly credit grant',
            ])
            ->addColumn('low_balance_notified_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Timestamp of last low-balance warning sent',
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
                'unique' => true,
                'name' => 'idx_notification_credits_org_unique',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notification_credits_organization',
            ])
            ->create();
    }
}
