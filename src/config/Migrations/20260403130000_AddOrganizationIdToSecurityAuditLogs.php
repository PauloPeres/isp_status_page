<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddOrganizationIdToSecurityAuditLogs Migration
 *
 * Fixes critical cross-tenant data leak: the security_audit_logs table
 * had no organization_id column, allowing any authenticated user to
 * read all organizations' audit logs.
 */
class AddOrganizationIdToSecurityAuditLogs extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('security_audit_logs');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'user_id',
                'comment' => 'Organization associated with the event (nullable for failed logins / system events)',
            ])
            ->addIndex(['organization_id', 'created'], [
                'name' => 'idx_audit_org_created',
                'order' => ['created' => 'DESC'],
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();

        // Backfill organization_id from user_id -> organization_users relationship
        $this->execute("
            UPDATE security_audit_logs
            SET organization_id = (
                SELECT ou.organization_id
                FROM organization_users ou
                WHERE ou.user_id = security_audit_logs.user_id
                LIMIT 1
            )
            WHERE user_id IS NOT NULL
              AND organization_id IS NULL
        ");
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('security_audit_logs');
        $table->dropForeignKey('organization_id');
        $table->removeColumn('organization_id');
        $table->update();
    }
}
