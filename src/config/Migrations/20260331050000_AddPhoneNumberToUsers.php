<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddPhoneNumberToUsers Migration
 *
 * Adds optional phone_number field to users table.
 */
class AddPhoneNumberToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        if (!$table->hasColumn('phone_number')) {
            $table->addColumn('phone_number', 'string', ['limit' => 30, 'null' => true, 'default' => null])
                ->update();
        }
    }
}
