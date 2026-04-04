<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Organization;
use App\Model\Entity\Plan;
use App\Model\Table\MonitorsTable;
use App\Model\Table\OrganizationUsersTable;
use App\Model\Table\OrganizationsTable;
use App\Model\Table\PlansTable;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

/**
 * Plan Service
 *
 * Manages plan-based limit enforcement and feature access checks.
 * Provides methods to verify whether an organization can perform
 * specific actions based on their subscription plan.
 *
 * Trial-aware: during an active trial period, organizations are treated
 * as Business plan regardless of the stored plan column. When the trial
 * expires and no Stripe subscription exists, the free plan applies.
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
     * In-memory cache for organization lookups
     *
     * @var array<int, \App\Model\Entity\Organization>
     */
    private array $orgCache = [];

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
     * Get the organization entity (cached).
     *
     * @param int $orgId Organization ID
     * @return \App\Model\Entity\Organization
     * @throws \RuntimeException If the organization is not found
     */
    private function getOrganization(int $orgId): Organization
    {
        if (isset($this->orgCache[$orgId])) {
            return $this->orgCache[$orgId];
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            throw new RuntimeException(sprintf('Organization with ID %d not found', $orgId));
        }

        $this->orgCache[$orgId] = $organization;

        return $organization;
    }

    /**
     * Check if an organization has an active Stripe subscription.
     *
     * @param \App\Model\Entity\Organization $org Organization entity
     * @return bool
     */
    private function hasActiveStripeSubscription(Organization $org): bool
    {
        return !empty($org->stripe_subscription_id);
    }

    /**
     * Check if an organization is currently in an active trial period.
     *
     * An org is on trial when:
     * - trial_ends_at is set
     * - trial_ends_at > NOW()
     * - No active Stripe subscription
     *
     * @param int $orgId Organization ID
     * @return bool
     */
    public function isOnTrial(int $orgId): bool
    {
        $org = $this->getOrganization($orgId);

        if ($org->trial_ends_at === null) {
            return false;
        }

        if ($this->hasActiveStripeSubscription($org)) {
            return false;
        }

        return $org->trial_ends_at->greaterThan(new DateTime());
    }

    /**
     * Get the number of days remaining in the trial.
     *
     * Returns 0 if not on trial or trial has expired.
     *
     * @param int $orgId Organization ID
     * @return int Days remaining
     */
    public function getTrialDaysRemaining(int $orgId): int
    {
        $org = $this->getOrganization($orgId);

        if ($org->trial_ends_at === null) {
            return 0;
        }

        $now = new DateTime();
        if ($org->trial_ends_at->lessThanOrEquals($now)) {
            return 0;
        }

        return (int)$now->diffInDays($org->trial_ends_at);
    }

    /**
     * Check if an organization's trial has expired (was set but is now past).
     *
     * @param int $orgId Organization ID
     * @return bool
     */
    public function isTrialExpired(int $orgId): bool
    {
        $org = $this->getOrganization($orgId);

        if ($org->trial_ends_at === null) {
            return false;
        }

        if ($this->hasActiveStripeSubscription($org)) {
            return false;
        }

        return $org->trial_ends_at->lessThanOrEquals(new DateTime());
    }

    /**
     * Get the effective plan for an organization, accounting for trial status.
     *
     * During an active trial, returns the Business plan regardless of stored plan.
     * After trial expiry with no subscription, returns the Free plan.
     *
     * @param int $orgId Organization ID
     * @return \App\Model\Entity\Plan
     */
    public function getEffectivePlan(int $orgId): Plan
    {
        if ($this->isOnTrial($orgId)) {
            $businessPlan = $this->Plans->find('bySlug', slug: Plan::SLUG_BUSINESS)->first();
            if ($businessPlan) {
                return $businessPlan;
            }
        }

        // Fall through to stored plan
        return $this->getPlanForOrganization($orgId);
    }

    /**
     * Get trial information for an organization (for API responses).
     *
     * @param int $orgId Organization ID
     * @return array{is_trial: bool, trial_expired: bool, trial_days_remaining: int, trial_ends_at: string|null, effective_plan: string}
     */
    public function getTrialInfo(int $orgId): array
    {
        $org = $this->getOrganization($orgId);
        $effectivePlan = $this->getEffectivePlan($orgId);

        return [
            'is_trial' => $this->isOnTrial($orgId),
            'trial_expired' => $this->isTrialExpired($orgId),
            'trial_days_remaining' => $this->getTrialDaysRemaining($orgId),
            'trial_ends_at' => $org->trial_ends_at ? $org->trial_ends_at->toIso8601String() : null,
            'effective_plan' => $effectivePlan->slug,
        ];
    }

    /**
     * Get the plan entity for an organization
     *
     * Looks up the organization's plan slug and returns the corresponding Plan entity.
     * This returns the STORED plan, not the effective plan. For trial-aware lookups,
     * use getEffectivePlan() instead.
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

        $organization = $this->getOrganization($orgId);

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
        $plan = $this->getEffectivePlan($orgId);

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
        $plan = $this->getEffectivePlan($orgId);

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
        $plan = $this->getEffectivePlan($orgId);

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
        $plan = $this->getEffectivePlan($orgId);

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
            $plan = $this->getEffectivePlan($orgId);

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
     * Check if an organization can add another status page
     */
    public function canAddStatusPage(int $orgId): bool
    {
        $plan = $this->getEffectivePlan($orgId);

        if ($plan->isUnlimited('status_page_limit')) {
            return true;
        }

        $currentCount = $this->fetchTable('StatusPages')->find()
            ->where(['StatusPages.organization_id' => $orgId])
            ->count();

        return $currentCount < $plan->status_page_limit;
    }

    /**
     * Validate and clamp a check interval to the plan minimum.
     */
    public function validateCheckInterval(int $orgId, int $requested): int
    {
        $minInterval = $this->getMinCheckInterval($orgId);
        return max($requested, $minInterval);
    }

    /**
     * Get a structured limit check result with current usage, limit, and upgrade info.
     *
     * @return array{allowed: bool, current: int, limit: int|string, plan_name: string, plan_slug: string, upgrade_to: string|null}
     */
    public function checkLimit(int $orgId, string $limitType): array
    {
        $plan = $this->getEffectivePlan($orgId);

        $current = 0;
        $limit = 0;
        $unlimited = false;

        switch ($limitType) {
            case 'monitor':
                $unlimited = $plan->isUnlimited('monitor_limit');
                $limit = $plan->monitor_limit;
                $current = $this->Monitors->find()
                    ->where(['Monitors.organization_id' => $orgId])
                    ->count();
                break;

            case 'team_member':
                $unlimited = $plan->isUnlimited('team_member_limit');
                $limit = $plan->team_member_limit;
                $current = $this->OrganizationUsers->find()
                    ->where(['OrganizationUsers.organization_id' => $orgId])
                    ->count();
                // Also count pending invitations
                try {
                    $pendingInvites = $this->fetchTable('Invitations')->find()
                        ->where([
                            'Invitations.organization_id' => $orgId,
                            'Invitations.status' => 'pending',
                        ])
                        ->count();
                    $current += $pendingInvites;
                } catch (\Exception $e) {
                    // Invitations table may not exist
                }
                break;

            case 'status_page':
                $unlimited = $plan->isUnlimited('status_page_limit');
                $limit = $plan->status_page_limit;
                $current = $this->fetchTable('StatusPages')->find()
                    ->where(['StatusPages.organization_id' => $orgId])
                    ->count();
                break;

            default:
                return ['allowed' => true, 'current' => 0, 'limit' => 'unlimited', 'plan_name' => $plan->name, 'plan_slug' => $plan->slug, 'upgrade_to' => null];
        }

        $allowed = $unlimited || $current < $limit;

        // Find the next plan up for the upgrade suggestion
        $upgradeTo = null;
        if (!$allowed) {
            $nextPlan = $this->Plans->find()
                ->where([
                    'Plans.active' => true,
                    'Plans.price_monthly >' => $plan->price_monthly,
                ])
                ->orderBy(['Plans.price_monthly' => 'ASC'])
                ->first();
            if ($nextPlan) {
                $upgradeTo = $nextPlan->slug;
            }
        }

        return [
            'allowed' => $allowed,
            'current' => $current,
            'limit' => $unlimited ? 'unlimited' : $limit,
            'plan_name' => $plan->name,
            'plan_slug' => $plan->slug,
            'upgrade_to' => $upgradeTo,
        ];
    }

    /**
     * Check if a feature is available and return structured result.
     */
    public function checkFeature(int $orgId, string $feature): array
    {
        $plan = $this->getEffectivePlan($orgId);
        $allowed = $plan->hasFeature($feature);

        $upgradeTo = null;
        if (!$allowed) {
            $nextPlan = $this->Plans->find()
                ->where([
                    'Plans.active' => true,
                    'Plans.price_monthly >' => $plan->price_monthly,
                ])
                ->orderBy(['Plans.price_monthly' => 'ASC'])
                ->first();
            if ($nextPlan) {
                $upgradeTo = $nextPlan->slug;
            }
        }

        return [
            'allowed' => $allowed,
            'feature' => $feature,
            'plan_name' => $plan->name,
            'plan_slug' => $plan->slug,
            'upgrade_to' => $upgradeTo,
        ];
    }

    /**
     * Get full usage summary for an organization (for billing/usage page).
     */
    public function getUsageSummary(int $orgId): array
    {
        $plan = $this->getEffectivePlan($orgId);

        $monitorCount = $this->Monitors->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->count();

        $teamCount = $this->OrganizationUsers->find()
            ->where(['OrganizationUsers.organization_id' => $orgId])
            ->count();

        $statusPageCount = 0;
        try {
            $statusPageCount = $this->fetchTable('StatusPages')->find()
                ->where(['StatusPages.organization_id' => $orgId])
                ->count();
        } catch (\Exception $e) {}

        $trialInfo = $this->getTrialInfo($orgId);

        return [
            'plan' => [
                'slug' => $plan->slug,
                'name' => $plan->name,
            ],
            'trial' => $trialInfo,
            'monitors' => [
                'current' => $monitorCount,
                'limit' => $plan->isUnlimited('monitor_limit') ? 'unlimited' : $plan->monitor_limit,
            ],
            'team_members' => [
                'current' => $teamCount,
                'limit' => $plan->isUnlimited('team_member_limit') ? 'unlimited' : $plan->team_member_limit,
            ],
            'status_pages' => [
                'current' => $statusPageCount,
                'limit' => $plan->isUnlimited('status_page_limit') ? 'unlimited' : $plan->status_page_limit,
            ],
            'check_interval_min' => $plan->check_interval_min,
            'data_retention_days' => $plan->data_retention_days,
            'features' => $plan->getFeatures(),
        ];
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
            $this->orgCache = [];
        } else {
            unset($this->planCache[$orgId]);
            unset($this->orgCache[$orgId]);
        }
    }
}
