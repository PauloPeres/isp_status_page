<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddAutoReplenishToOrganizations Migration
 *
 * Adds auto-replenish monthly cap and last charged timestamp to notification_credits.
 * The base auto_recharge fields already exist; this adds the monthly spending cap
 * and tracking fields needed for the auto-replenish feature.
 */
class AddAutoReplenishToOrganizations extends AbstractMigration
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
            ->addColumn('auto_replenish_max_monthly', 'integer', [
                'null' => false,
                'default' => 500,
                'after' => 'auto_recharge_amount',
                'comment' => 'Maximum credits that can be auto-purchased per calendar month',
            ])
            ->addColumn('auto_replenish_last_charged_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'auto_replenish_max_monthly',
                'comment' => 'Timestamp of last successful auto-replenish charge',
            ])
            ->update();
    }
}
