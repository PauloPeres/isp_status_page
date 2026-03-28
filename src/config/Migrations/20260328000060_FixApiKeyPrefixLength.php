<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * FixApiKeyPrefixLength Migration
 *
 * BUG 3 fix: The key_prefix column was created with limit=10 but
 * the generated prefix is 12 characters. Widen to 16 for safety.
 */
class FixApiKeyPrefixLength extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('api_keys');
        $table->changeColumn('key_prefix', 'string', [
            'limit' => 16,
            'null' => false,
            'comment' => 'First 12 chars of key for lookup',
        ]);
        $table->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('api_keys');
        $table->changeColumn('key_prefix', 'string', [
            'limit' => 10,
            'null' => false,
            'comment' => 'First 12 chars of key for lookup',
        ]);
        $table->update();
    }
}
