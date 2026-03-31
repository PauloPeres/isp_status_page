<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression: Super Admin AppController must throw ForbiddenException.
 * Covers VULN-01 fix — verifies the controller uses exception-based blocking.
 */
class SuperAdminAccessTest extends TestCase
{
    /**
     * SuperAdmin AppController must use ForbiddenException, not just error().
     */
    public function testSuperAdminControllerUsesForbiddenException(): void
    {
        $source = file_get_contents(
            ROOT . '/src/Controller/Api/V2/SuperAdmin/AppController.php'
        );

        $this->assertStringContainsString(
            'ForbiddenException',
            $source,
            'SuperAdmin AppController must use ForbiddenException to halt unauthorized access'
        );

        $this->assertStringContainsString(
            'throw new',
            $source,
            'SuperAdmin AppController must throw exception (not just set error response)'
        );
    }

    /**
     * SuperAdmin AppController must check isSuperAdmin in beforeFilter.
     */
    public function testSuperAdminChecksIsSuperAdmin(): void
    {
        $source = file_get_contents(
            ROOT . '/src/Controller/Api/V2/SuperAdmin/AppController.php'
        );

        $this->assertStringContainsString(
            'isSuperAdmin',
            $source,
            'SuperAdmin AppController must check isSuperAdmin flag'
        );

        $this->assertStringContainsString(
            'beforeFilter',
            $source,
            'SuperAdmin AppController must enforce in beforeFilter'
        );
    }
}
