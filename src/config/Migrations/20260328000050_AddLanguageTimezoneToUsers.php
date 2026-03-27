<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddLanguageTimezoneToUsers Migration
 *
 * Adds per-user language and timezone preferences (TASK-1100).
 */
class AddLanguageTimezoneToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');

        $table
            ->addColumn('language', 'string', [
                'limit' => 10,
                'default' => 'en',
                'null' => false,
                'comment' => 'User preferred language (e.g. en, pt_BR, es)',
            ])
            ->addColumn('timezone', 'string', [
                'limit' => 50,
                'default' => 'UTC',
                'null' => false,
                'comment' => 'User preferred timezone (e.g. UTC, America/Sao_Paulo)',
            ])
            ->update();
    }
}
