<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSecurityAuditLogs Migration (TASK-AUTH-018)
 *
 * Creates the security_audit_logs table for tracking security-relevant events.
 */
class CreateSecurityAuditLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('security_audit_logs', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'User associated with the event (nullable for failed logins)',
            ])
            ->addColumn('event_type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Type of security event',
            ])
            ->addColumn('ip_address', 'string', [
                'limit' => 45,
                'null' => false,
                'comment' => 'Client IP address (supports IPv6)',
            ])
            ->addColumn('user_agent', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Client user agent string',
            ])
            ->addColumn('details', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON-encoded event details',
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'When the event occurred',
            ])
            ->addIndex(['user_id', 'created'], [
                'name' => 'idx_audit_user_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addIndex(['event_type', 'created'], [
                'name' => 'idx_audit_event_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addIndex(['ip_address', 'created'], [
                'name' => 'idx_audit_ip_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
