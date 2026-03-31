<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression: Bulk operations must include organization_id.
 * Covers VULN-03/05 fixes.
 */
class BulkOperationScopeTest extends TestCase
{
    /**
     * MonitorsController bulk operations must scope by organization_id.
     */
    public function testBulkActionsIncludeOrgId(): void
    {
        $source = file_get_contents(
            ROOT . '/src/Controller/Api/V2/MonitorsController.php'
        );

        // Check all updateAll calls include organization_id
        preg_match_all('/updateAll\([^)]+\)/', $source, $updateMatches);
        foreach ($updateMatches[0] as $match) {
            $this->assertStringContainsString(
                'organization_id',
                $match,
                "updateAll() must include organization_id: {$match}"
            );
        }

        // Check deleteAll calls include organization_id
        preg_match_all('/deleteAll\([^)]+\)/', $source, $deleteMatches);
        foreach ($deleteMatches[0] as $match) {
            $this->assertStringContainsString(
                'organization_id',
                $match,
                "deleteAll() must include organization_id: {$match}"
            );
        }
    }

    /**
     * Raw SQL in MonitorsController::view() must filter by organization_id.
     */
    public function testRawSqlIncludesOrgId(): void
    {
        $source = file_get_contents(
            ROOT . '/src/Controller/Api/V2/MonitorsController.php'
        );

        // Find raw SQL strings containing monitor_checks
        preg_match_all('/FROM monitor_checks[^"]+/s', $source, $matches);

        foreach ($matches[0] as $match) {
            $this->assertStringContainsString(
                'organization_id',
                $match,
                "Raw SQL querying monitor_checks must include organization_id"
            );
        }
    }

    /**
     * Search LIKE queries must escape wildcards.
     */
    public function testSearchEscapesWildcards(): void
    {
        $source = file_get_contents(
            ROOT . '/src/Controller/Api/V2/MonitorsController.php'
        );

        // If there's a LIKE query with $search, there must be wildcard escaping nearby
        if (str_contains($source, "LIKE'")) {
            $this->assertStringContainsString(
                'str_replace',
                $source,
                'LIKE queries must escape SQL wildcards with str_replace'
            );
        }
    }
}
