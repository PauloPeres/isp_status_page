<?php
declare(strict_types=1);

namespace App\Tenant;

/**
 * Thread-safe holder for the current tenant (organization) context.
 * Set by TenantMiddleware, read by TenantScopeBehavior.
 */
class TenantContext
{
    private static ?int $currentOrgId = null;
    private static ?array $currentOrganization = null;

    /**
     * Set the current organization ID.
     *
     * @param int|null $orgId The organization ID.
     * @return void
     */
    public static function setCurrentOrgId(?int $orgId): void
    {
        static::$currentOrgId = $orgId;
    }

    /**
     * Get the current organization ID.
     *
     * @return int|null
     */
    public static function getCurrentOrgId(): ?int
    {
        return static::$currentOrgId;
    }

    /**
     * Set the current organization data.
     *
     * @param array|null $org The organization data array.
     * @return void
     */
    public static function setCurrentOrganization(?array $org): void
    {
        static::$currentOrganization = $org;
    }

    /**
     * Get the current organization data.
     *
     * @return array|null
     */
    public static function getCurrentOrganization(): ?array
    {
        return static::$currentOrganization;
    }

    /**
     * Clear context (for testing/CLI).
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$currentOrgId = null;
        static::$currentOrganization = null;
    }

    /**
     * Check if the tenant context has been set.
     *
     * @return bool
     */
    public static function isSet(): bool
    {
        return static::$currentOrgId !== null;
    }
}
