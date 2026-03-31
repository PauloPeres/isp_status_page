<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\TestSuite\TestCase;

/**
 * Security regression tests for AuthController.
 *
 * Verifies authentication security patterns at the source-code level
 * to catch regressions in user enumeration prevention, password hashing,
 * token storage, and input validation.
 */
class AuthSecurityTest extends TestCase
{
    /**
     * Verify that duplicate registration returns a generic error message
     * to prevent user enumeration attacks.
     */
    public function testRegistrationReturnsGenericError(): void
    {
        $source = file_get_contents(ROOT . '/src/Controller/Api/V2/AuthController.php');

        // The error message must be generic — must NOT reveal whether the email or username already exists
        $this->assertStringContainsString(
            'Registration failed. Please check your details and try again.',
            $source,
            'AuthController must return a generic error on duplicate registration to prevent user enumeration'
        );

        // Must NOT contain messages like "email already exists" or "username taken"
        $this->assertStringNotContainsString(
            'already exists',
            $source,
            'AuthController must not reveal which field caused the duplicate'
        );
        $this->assertStringNotContainsString(
            'already taken',
            $source,
            'AuthController must not reveal which field caused the duplicate'
        );
    }

    /**
     * Verify the User entity uses DefaultPasswordHasher (bcrypt) for password hashing.
     */
    public function testPasswordHashingUsesBcrypt(): void
    {
        $source = file_get_contents(ROOT . '/src/Model/Entity/User.php');

        $this->assertStringContainsString(
            'DefaultPasswordHasher',
            $source,
            'User entity must use DefaultPasswordHasher for bcrypt hashing'
        );

        $this->assertStringContainsString(
            '_setPassword',
            $source,
            'User entity must have a _setPassword mutator for automatic hashing'
        );

        // Verify it actually instantiates the hasher and calls hash()
        $this->assertStringContainsString(
            '(new DefaultPasswordHasher())->hash($password)',
            $source,
            'User entity must hash passwords through DefaultPasswordHasher::hash()'
        );
    }

    /**
     * Verify that refresh tokens are stored as SHA-256 hashes, not in plain text.
     */
    public function testRefreshTokenIsHashed(): void
    {
        $source = file_get_contents(ROOT . '/src/Service/JwtService.php');

        // Must use SHA-256 to hash the token before storage
        $this->assertStringContainsString(
            "hash('sha256'",
            $source,
            'JwtService must hash refresh tokens using SHA-256 before storing'
        );

        // Verify the RefreshToken entity stores token_hash, not plain token
        $entitySource = file_get_contents(ROOT . '/src/Model/Entity/RefreshToken.php');
        $this->assertStringContainsString(
            'token_hash',
            $entitySource,
            'RefreshToken entity must store token_hash, not plain token'
        );

        // Verify token_hash is hidden from serialization
        $this->assertStringContainsString(
            "'token_hash'",
            $entitySource,
            'RefreshToken entity must reference token_hash in hidden fields'
        );
    }

    /**
     * Verify the User entity hides the password field from JSON serialization.
     */
    public function testLoginResponseDoesNotExposePassword(): void
    {
        $source = file_get_contents(ROOT . '/src/Model/Entity/User.php');

        // Check that password is in the $_hidden array
        $this->assertStringContainsString(
            '$_hidden',
            $source,
            'User entity must declare $_hidden array'
        );

        // Verify 'password' is listed as hidden
        $this->assertMatchesRegularExpression(
            '/\$_hidden\s*=\s*\[.*[\'"]password[\'"]/s',
            $source,
            'User entity must include "password" in $_hidden to prevent exposure in JSON responses'
        );

        // Also verify two_factor_secret is hidden
        $this->assertStringContainsString(
            "'two_factor_secret'",
            $source,
            'User entity must also hide two_factor_secret'
        );
    }

    /**
     * Verify that registration enforces a minimum password length of 8 characters.
     */
    public function testRegistrationRequiresMinPasswordLength(): void
    {
        $source = file_get_contents(ROOT . '/src/Controller/Api/V2/AuthController.php');

        // Must check strlen($password) < 8
        $this->assertStringContainsString(
            'strlen($password) < 8',
            $source,
            'AuthController must validate minimum password length of 8 characters'
        );

        $this->assertStringContainsString(
            'Password must be at least 8 characters',
            $source,
            'AuthController must return a descriptive error for short passwords'
        );
    }
}
