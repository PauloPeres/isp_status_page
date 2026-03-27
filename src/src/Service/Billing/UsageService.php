<?php
declare(strict_types=1);

namespace App\Service\Billing;

use App\Model\Entity\Plan;
use App\Model\Table\MonitorsTable;
use App\Model\Table\OrganizationUsersTable;
use App\Model\Table\OrganizationsTable;
use App\Model\Table\PlansTable;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Usage Service
 *
 * Tracks resource usage per organization and enforces plan limits.
 * Provides methods to check current usage counts, whether an action
 * is allowed under the plan, and usage percentages for display.
 */
class UsageService
{
    use LocatorAwareTrait;

    /**
     * Organizations table instance
     *
     * @var \App\Model\Table\OrganizationsTable
     */
    private OrganizationsTable $Organizations;

    /**
     * Plans table instance
     *
     * @var \App\Model\Table\PlansTable
     */
    private PlansTable $Plans;

    /**
     * Monitors table instance
     *
     * @var \App\Model\Table\MonitorsTable
     */
    private MonitorsTable $Monitors;

    /**
     * OrganizationUsers table instance
     *
     * @var \App\Model\Table\OrganizationUsersTable
     */
    private OrganizationUsersTable $OrganizationUsers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Organizations = $this->fetchTable('Organizations');
        $this->Plans = $this->fetchTable('Plans');
        $this->Monitors = $this->fetchTable('Monitors');
        $this->OrganizationUsers = $this->fetchTable('OrganizationUsers');
    }

    /**
     * Get current resource usage for an organization
     *
     * Returns an associative array of resource counts.
     *
     * @param int $orgId Organization ID
     * @return array<string, int> Usage counts keyed by resource name
     */
    public function getUsage(int $orgId): array
    {
        $monitors = $this->Monitors->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->count();

        $teamMembers = $this->OrganizationUsers->find()
            ->where(['OrganizationUsers.organization_id' => $orgId])
            ->count();

        return [
            'monitors' => $monitors,
            'team_members' => $teamMembers,
        ];
    }

    /**
     * Check if an organization can perform a specific action
     *
     * Supported actions: 'add_monitor', 'add_team_member'
     *
     * @param int $orgId Organization ID
     * @param string $action The action to check
     * @return bool True if the action is allowed
     */
    public function canPerform(int $orgId, string $action): bool
    {
        $plan = $this->getPlanForOrganization($orgId);
        if (!$plan) {
            return false;
        }

        $usage = $this->getUsage($orgId);

        return match ($action) {
            'add_monitor' => $plan->isUnlimited('monitor_limit')
                || $usage['monitors'] < $plan->monitor_limit,
            'add_team_member' => $plan->isUnlimited('team_member_limit')
                || $usage['team_members'] < $plan->team_member_limit,
            default => false,
        };
    }

    /**
     * Get usage percentage for a specific resource
     *
     * Returns a float between 0.0 and 100.0 representing how much of the
     * limit is consumed. Returns 0.0 for unlimited resources.
     *
     * @param int $orgId Organization ID
     * @param string $resource The resource to check ('monitors', 'team_members')
     * @return float Usage percentage (0.0 - 100.0)
     */
    public function getUsagePercentage(int $orgId, string $resource): float
    {
        $plan = $this->getPlanForOrganization($orgId);
        if (!$plan) {
            return 0.0;
        }

        $usage = $this->getUsage($orgId);

        $limitField = match ($resource) {
            'monitors' => 'monitor_limit',
            'team_members' => 'team_member_limit',
            default => null,
        };

        if ($limitField === null) {
            return 0.0;
        }

        if ($plan->isUnlimited($limitField)) {
            return 0.0;
        }

        $limit = (int)$plan->get($limitField);
        if ($limit <= 0) {
            return 100.0;
        }

        $current = $usage[$resource] ?? 0;

        return min(100.0, ($current / $limit) * 100.0);
    }

    /**
     * Get the plan limits for an organization
     *
     * Returns an associative array of limit values for display purposes.
     *
     * @param int $orgId Organization ID
     * @return array<string, int|string> Limits keyed by resource name, 'unlimited' for -1 values
     */
    public function getLimits(int $orgId): array
    {
        $plan = $this->getPlanForOrganization($orgId);
        if (!$plan) {
            return [];
        }

        return [
            'monitors' => $plan->isUnlimited('monitor_limit') ? 'unlimited' : $plan->monitor_limit,
            'team_members' => $plan->isUnlimited('team_member_limit') ? 'unlimited' : $plan->team_member_limit,
            'status_pages' => $plan->isUnlimited('status_page_limit') ? 'unlimited' : $plan->status_page_limit,
            'api_rate_limit' => $plan->api_rate_limit,
            'data_retention_days' => $plan->data_retention_days,
            'check_interval_min' => $plan->check_interval_min,
        ];
    }

    /**
     * Get the plan entity for an organization
     *
     * @param int $orgId Organization ID
     * @return \App\Model\Entity\Plan|null
     */
    private function getPlanForOrganization(int $orgId): ?Plan
    {
        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            Log::error("Organization {$orgId} not found for usage check");

            return null;
        }

        $plan = $this->Plans->find('bySlug', slug: $organization->plan)->first();
        if (!$plan) {
            Log::error("Plan '{$organization->plan}' not found for org {$orgId}");

            return null;
        }

        return $plan;
    }
}
