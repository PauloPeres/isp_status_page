<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddBadgeTokenToMonitors Migration
 *
 * BUG 5 fix: Badge endpoints crash because monitors table is missing
 * the badge_token column that BadgesController expects.
 */
class AddBadgeTokenToMonitors extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('monitors');
        $table->addColumn('badge_token', 'string', [
            'limit' => 64,
            'null' => true,
            'default' => null,
            'comment' => 'Unique token for public badge endpoints',
        ]);
        $table->addIndex(['badge_token'], [
            'name' => 'idx_monitors_badge_token',
            'unique' => true,
        ]);
        $table->update();

        // Generate tokens for existing monitors
        $monitors = $this->fetchAll('SELECT id FROM monitors');
        foreach ($monitors as $monitor) {
            $token = bin2hex(random_bytes(32));
            $this->execute(
                sprintf(
                    "UPDATE monitors SET badge_token = '%s' WHERE id = %d",
                    $token,
                    $monitor['id']
                )
            );
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('monitors');
        $table->removeIndex(['badge_token']);
        $table->removeColumn('badge_token');
        $table->update();
    }
}
