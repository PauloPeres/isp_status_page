<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddLanguageToStatusPages extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('status_pages');
        if (!$table->hasColumn('language')) {
            $table->addColumn('language', 'string', [
                'limit' => 10,
                'default' => 'en',
                'null' => false,
                'after' => 'active',
            ])->update();
        }
    }
}
