<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * AdminOrgSeed
 *
 * BUG 8 fix: Ensures the admin user (id=1) has an organization membership.
 * Creates a "Default Organization" if one does not already exist,
 * then links user 1 to it as owner in organization_users.
 */
class AdminOrgSeed extends AbstractSeed
{
    /**
     * @var array<string>
     */
    public array $dependencies = [
        'UsersSeed',
    ];

    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Check if a "default" organization already exists
        $org = $this->fetchRow("SELECT id FROM organizations WHERE slug = 'default'");

        if (!$org) {
            $this->table('organizations')->insert([
                'name' => 'Default Organization',
                'slug' => 'default',
                'plan' => 'free',
                'timezone' => 'UTC',
                'language' => 'en',
                'active' => true,
                'created' => $now,
                'modified' => $now,
            ])->saveData();

            // Re-fetch to get the auto-assigned ID
            $org = $this->fetchRow("SELECT id FROM organizations WHERE slug = 'default'");
        }

        if (!$org) {
            // Could not create or find org — skip
            return;
        }

        $orgId = $org['id'] ?? $org[0] ?? null;
        if (!$orgId) {
            return;
        }

        // Check if the link already exists
        $existing = $this->fetchRow(
            "SELECT id FROM organization_users WHERE organization_id = {$orgId} AND user_id = 1"
        );

        if (!$existing) {
            $this->table('organization_users')->insert([
                'organization_id' => (int)$orgId,
                'user_id' => 1,
                'role' => 'owner',
                'accepted_at' => $now,
                'created' => $now,
                'modified' => $now,
            ])->saveData();
        }
    }
}
