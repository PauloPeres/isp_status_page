<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPasswordResetToUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('reset_token', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->addColumn('reset_token_expires', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex([
            'reset_token',
        
            ], [
            'name' => 'BY_RESET_TOKEN',
            'unique' => false,
        ]);
        $table->addIndex([
            'reset_token_expires',
        
            ], [
            'name' => 'BY_RESET_TOKEN_EXPIRES',
            'unique' => false,
        ]);
        $table->update();
    }
}
