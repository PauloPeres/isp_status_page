<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAcknowledgementToIncidents extends BaseMigration
{
    /**
     * Change Method.
     *
     * Adds acknowledgement columns to the incidents table for TASK-260.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('incidents');
        $table->addColumn('acknowledged_by_user_id', 'integer', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('acknowledged_at', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('acknowledged_via', 'string', [
            'default' => null,
            'limit' => 20,
            'null' => true,
        ]);
        $table->addColumn('acknowledgement_token', 'string', [
            'default' => null,
            'limit' => 64,
            'null' => true,
        ]);
        $table->addIndex([
            'acknowledgement_token',
        ], [
            'name' => 'BY_ACKNOWLEDGEMENT_TOKEN',
            'unique' => false,
        ]);
        $table->addIndex([
            'acknowledged_by_user_id',
        ], [
            'name' => 'BY_ACKNOWLEDGED_USER',
            'unique' => false,
        ]);
        $table->update();
    }
}
