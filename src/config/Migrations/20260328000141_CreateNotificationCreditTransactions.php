<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateNotificationCreditTransactions Migration
 *
 * Creates the notification_credit_transactions table for tracking all credit
 * movements: usage, purchases, monthly grants, manual adjustments, and refunds.
 */
class CreateNotificationCreditTransactions extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('notification_credit_transactions', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'null' => false,
            ])
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this transaction belongs to',
            ])
            ->addColumn('type', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Transaction type: usage, purchase, monthly_grant, manual_adjustment, refund',
            ])
            ->addColumn('amount', 'integer', [
                'null' => false,
                'comment' => 'Credit amount (positive for additions, negative for usage)',
            ])
            ->addColumn('balance_after', 'integer', [
                'null' => false,
                'comment' => 'Credit balance after this transaction',
            ])
            ->addColumn('channel', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'comment' => 'Notification channel for usage type (sms, whatsapp)',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Human-readable description of the transaction',
            ])
            ->addColumn('reference_id', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
                'comment' => 'External reference (Stripe payment ID, alert_log ID, etc.)',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addIndex(['organization_id', 'created'], [
                'name' => 'idx_credit_transactions_org_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addIndex(['type', 'created'], [
                'name' => 'idx_credit_transactions_type_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_credit_transactions_organization',
            ])
            ->create();
    }
}
