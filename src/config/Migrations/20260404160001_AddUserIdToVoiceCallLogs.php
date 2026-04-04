<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddUserIdToVoiceCallLogs Migration
 *
 * Adds a nullable user_id foreign key to the voice_call_logs table
 * for auditable acknowledgement tracking.
 */
class AddUserIdToVoiceCallLogs extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('voice_call_logs');

        $table
            ->addColumn('user_id', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => true,
                'after' => 'organization_id',
            ])
            ->addIndex(['user_id'], ['name' => 'idx_voice_call_logs_user_id'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_voice_call_logs_user_id',
            ])
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('voice_call_logs');

        $table->dropForeignKey('user_id');
        $table->removeColumn('user_id');
        $table->update();
    }
}
