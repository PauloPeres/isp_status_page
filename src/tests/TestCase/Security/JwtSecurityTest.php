<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression: JWT configuration must be secure.
 * Covers VULN-02 fix.
 */
class JwtSecurityTest extends TestCase
{
    /**
     * JwtService source code must reject empty secrets.
     */
    public function testJwtServiceCodeRejectsEmptySecret(): void
    {
        $source = file_get_contents(ROOT . '/src/Service/JwtService.php');

        $this->assertStringContainsString(
            "empty(\$secret)",
            $source,
            'JwtService must check for empty secret'
        );

        $this->assertStringContainsString(
            'RuntimeException',
            $source,
            'JwtService must throw RuntimeException for missing secret'
        );
    }

    /**
     * JwtService source code must reject known weak defaults in production.
     */
    public function testJwtServiceCodeRejectsWeakDefaults(): void
    {
        $source = file_get_contents(ROOT . '/src/Service/JwtService.php');

        $this->assertStringContainsString(
            'change-me',
            $source,
            'JwtService must check for the "change-me" default value'
        );

        $this->assertStringContainsString(
            'APP_DEBUG',
            $source,
            'JwtService must check APP_DEBUG to enforce in production only'
        );
    }
}
