<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Plan;
use App\Model\Table\MonitorsTable;
use App\Model\Table\OrganizationUsersTable;
use App\Model\Table\OrganizationsTable;
use App\Model\Table\PlansTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

/**
 * Plan Service
 *
 * Manages plan-based limit enforcement and feature access checks.
 * Provides methods to verify whether an organization can perform
 * specific actions based on their subscription plan.
 */
class PlanService
{
    use LocatorAwareTrait;
    use LoggerAwareTrait;

    /**
     * Plans table instance
     *
     * @var \App\Model\Table\PlansTable
     */
    private PlansTable $Plans;

    /**
     * Organizations table instance
     *
     * @var \App\Model\Table\OrganizationsTable
     */
    private OrganizationsTable $Organizations;

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
     * In-memory cache for plan lookups
     *
     * @var array<int, \App\Model\Entity\Plan>
     */
    private array $planCache = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Plans = $this->fetchTable('Plans');
        $this->Organizations = $this->fetchTable('Organizations');
        $this->Monitors = $this->fetchTable('Monitors');
        $this->OrganizationUsers = $this->fetchTable('OrganizationUsers');
    }

    /**
     * Get the plan entity for an organization
     *
     * Looks up the organization's plan slug and returns the corresponding Plan entity.
     *
     * @param int $orgId Organization ID
     * @return \App\Model\Entity\Plan
     * @throws \RuntimeException If the organization or plan is not found
     */
    public function getPlanForOrganization(int $orgId): Plan
    {
        if (isset($this->planCache[$orgId])) {
            return $this->planCache[$orgId];
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            throw new RuntimeException(sprintf('Organization with ID %d not found', $orgId));
        }

        $plan = $this->Plans->find('bySlug', slug: $organization->plan)->first();

        if (!$plan) {
            throw new RuntimeException(sprintf(
                'Plan "%s" not found for organization ID %d',
                $organization->plan,
                $orgId
            ));
        }

        $this->planCache[$orgId] = $plan;

        return $plan;
    }

    /**
     * Check if an organization can add another monitor
     *
     * Compares current monitor count against the plan's monitor_limit.
     * Returns true if the limit is unlimited (-1) or if the count is below the limit.
     *
     * @param int $orgId Organization ID
     * @return bool
     */
    public function canAddMonitor(int $orgId): bool
    {
        $plan = $this->getPlanForOrganization($orgId);

        if ($plan->isUnlimited('monitor_limit')) {
            return true;
        }

        $currentCount = $this->Monitors->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->count();

        return $currentCount < $plan->monitor_limit;
    }

    /**
     * Check if an organization can add another team member
     *
     * Compares current team member count against the plan's team_member_limit.
     * Returns true if the limit is unlimited (-1) or if the count is below the limit.
     *
     * @param int $orgId Organization ID
     * @return bool
     */
    public function canAddTeamMember(int $orgId): bool
    {
        $plan = $this->getPlanForOrganization($orgId);

        if ($plan->isUnlimited('team_member_limit')) {
            return true;
        }

        $currentCount = $this->OrganizationUsers->find()
            ->where(['OrganizationUsers.organization_id' => $orgId])
            ->count();

        return $currentCount < $plan->team_member_limit;
    }

    /**
     * Check if an organization can use a specific feature
     *
     * Checks the plan's JSON feature flags for the given feature key.
     *
     * @param int $orgId Organization ID
     * @param string $feature Feature key to check (e.g., 'slack_alerts', 'api_access')
     * @return bool
     */
    public function canUseFeature(int $orgId, string $feature): bool
    {
        $plan = $this->getPlanForOrganization($orgId);

        return $plan->hasFeature($feature);
    }

    /**
     * Get the minimum allowed check interval for an organization
     *
     * Returns the minimum check interval in seconds allowed by the plan.
     *
     * @param int $orgId Organization ID
     * @return int Minimum check interval in seconds
     */
    public function getMinCheckInterval(int $orgId): int
    {
        $plan = $this->getPlanForOrganization($orgId);

        return $plan->check_interval_min;
    }

    /**
     * Enforce a plan limit, throwing an exception if the limit is exceeded
     *
     * Supported limit types: 'monitor', 'team_member'
     *
     * @param int $orgId Organization ID
     * @param string $limitType The type of limit to enforce ('monitor', 'team_member')
     * @return void
     * @throws \RuntimeException If the limit has been reached
     */
    public function enforceLimit(int $orgId, string $limitType): void
    {
        $allowed = match ($limitType) {
            'monitor' => $this->canAddMonitor($orgId),
            'team_member' => $this->canAddTeamMember($orgId),
            default => throw new RuntimeException(sprintf('Unknown limit type: %s', $limitType)),
        };

        if (!$allowed) {
            $plan = $this->getPlanForOrganization($orgId);

            $limitValue = match ($limitType) {
                'monitor' => $plan->monitor_limit,
                'team_member' => $plan->team_member_limit,
            };

            if ($this->logger) {
                $this->logger->warning('Plan limit reached', [
                    'organization_id' => $orgId,
                    'plan' => $plan->slug,
                    'limit_type' => $limitType,
                    'limit_value' => $limitValue,
                ]);
            }

            throw new RuntimeException(sprintf(
                'Plan limit reached: your %s plan allows a maximum of %d %s(s). Please upgrade to add more.',
                $plan->name,
                $limitValue,
                str_replace('_', ' ', $limitType)
            ));
        }
    }

    /**
     * Clear the in-memory plan cache for an organization
     *
     * Useful when an organization's plan changes.
     *
     * @param int|null $orgId Organization ID, or null to clear all
     * @return void
     */
    public function clearCache(?int $orgId = null): void
    {
        if ($orgId === null) {
            $this->planCache = [];
        } else {
            unset($this->planCache[$orgId]);
        }
    }
}
