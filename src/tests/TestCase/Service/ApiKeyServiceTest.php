<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\ApiKey;
use App\Service\ApiKeyService;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Service\ApiKeyService Test Case
 */
class ApiKeyServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.ApiKeys',
    ];

    /**
     * @var \App\Service\ApiKeyService
     */
    protected ApiKeyService $service;

    /**
     * @var \App\Model\Table\ApiKeysTable
     */
    protected $apiKeysTable;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiKeyService();
        $this->apiKeysTable = TableRegistry::getTableLocator()->get('ApiKeys');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->service, $this->apiKeysTable);
        parent::tearDown();
    }

    /**
     * Test key generation format
     *
     * @return void
     */
    public function testGenerateKeyFormat(): void
    {
        $result = $this->service->generate(1, 1, 'Test Key');

        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('entity', $result);

        $plainKey = $result['key'];

        // Key must start with sk_live_
        $this->assertStringStartsWith('sk_live_', $plainKey);

        // Key should be sk_live_ (8 chars) + 64 hex chars = 72 chars total
        $this->assertEquals(72, strlen($plainKey));

        // Verify the entity was saved
        $entity = $result['entity'];
        $this->assertInstanceOf(ApiKey::class, $entity);
        $this->assertNotEmpty($entity->id);
        $this->assertEquals('Test Key', $entity->name);
        $this->assertEquals(1, $entity->organization_id);
        $this->assertEquals(1, $entity->user_id);
        $this->assertTrue($entity->active);
    }

    /**
     * Test key generation stores correct prefix
     *
     * @return void
     */
    public function testGenerateKeyPrefix(): void
    {
        $result = $this->service->generate(1, 1, 'Prefix Test');

        $plainKey = $result['key'];
        $entity = $result['entity'];

        // Prefix should be the first 12 characters of the key
        $expectedPrefix = substr($plainKey, 0, 12);
        $this->assertEquals($expectedPrefix, $entity->key_prefix);
    }

    /**
     * Test key generation stores correct permissions
     *
     * @return void
     */
    public function testGenerateKeyPermissions(): void
    {
        $result = $this->service->generate(1, 1, 'Perms Test', ['read', 'write']);
        $entity = $result['entity'];

        $perms = $entity->getPermissions();
        $this->assertContains('read', $perms);
        $this->assertContains('write', $perms);
        $this->assertNotContains('admin', $perms);
    }

    /**
     * Test key generation defaults to read permission
     *
     * @return void
     */
    public function testGenerateKeyDefaultPermissions(): void
    {
        $result = $this->service->generate(1, 1, 'Default Perms');
        $entity = $result['entity'];

        $perms = $entity->getPermissions();
        $this->assertContains('read', $perms);
    }

    /**
     * Test key validation succeeds with valid key
     *
     * @return void
     */
    public function testValidateValidKey(): void
    {
        $result = $this->service->generate(1, 1, 'Validate Test');
        $plainKey = $result['key'];

        $validated = $this->service->validate($plainKey);

        $this->assertNotNull($validated);
        $this->assertInstanceOf(ApiKey::class, $validated);
        $this->assertEquals($result['entity']->id, $validated->id);
        $this->assertNotNull($validated->last_used_at);
    }

    /**
     * Test key validation fails with invalid key
     *
     * @return void
     */
    public function testValidateInvalidKey(): void
    {
        $validated = $this->service->validate('sk_live_invalid_key_that_does_not_exist_in_db');
        $this->assertNull($validated);
    }

    /**
     * Test key validation fails with wrong format
     *
     * @return void
     */
    public function testValidateWrongFormat(): void
    {
        $this->assertNull($this->service->validate(''));
        $this->assertNull($this->service->validate('short'));
        $this->assertNull($this->service->validate('not_an_api_key_at_all'));
    }

    /**
     * Test expired key rejection
     *
     * @return void
     */
    public function testValidateExpiredKeyRejected(): void
    {
        // Generate a key with expiration in the past
        $result = $this->service->generate(
            1,
            1,
            'Expiring Key',
            ['read'],
            1000,
            new DateTime('-1 hour')
        );

        $plainKey = $result['key'];

        // Should return null because the key is expired
        $validated = $this->service->validate($plainKey);
        $this->assertNull($validated);
    }

    /**
     * Test key revocation
     *
     * @return void
     */
    public function testRevokeKey(): void
    {
        $result = $this->service->generate(1, 1, 'Revoke Test');
        $entity = $result['entity'];

        $revoked = $this->service->revoke((int)$entity->id);
        $this->assertTrue($revoked);

        // Verify the key is now inactive
        $updated = $this->apiKeysTable->get($entity->id);
        $this->assertFalse($updated->active);
    }

    /**
     * Test revoked key fails validation
     *
     * @return void
     */
    public function testValidateRevokedKeyFails(): void
    {
        $result = $this->service->generate(1, 1, 'Revoke Validate Test');
        $plainKey = $result['key'];

        // Revoke the key
        $this->service->revoke((int)$result['entity']->id);

        // Validation should fail
        $validated = $this->service->validate($plainKey);
        $this->assertNull($validated);
    }

    /**
     * Test entity hasPermission method
     *
     * @return void
     */
    public function testEntityHasPermission(): void
    {
        $result = $this->service->generate(1, 1, 'Perm Check', ['read', 'write']);
        $entity = $result['entity'];

        $this->assertTrue($entity->hasPermission('read'));
        $this->assertTrue($entity->hasPermission('write'));
        $this->assertFalse($entity->hasPermission('admin'));
    }

    /**
     * Test entity admin permission grants all access
     *
     * @return void
     */
    public function testEntityAdminPermissionGrantsAll(): void
    {
        $result = $this->service->generate(1, 1, 'Admin Key', ['admin']);
        $entity = $result['entity'];

        $this->assertTrue($entity->hasPermission('read'));
        $this->assertTrue($entity->hasPermission('write'));
        $this->assertTrue($entity->hasPermission('admin'));
    }

    /**
     * Test entity isExpired method
     *
     * @return void
     */
    public function testEntityIsExpired(): void
    {
        $entity = new ApiKey([
            'expires_at' => new DateTime('-1 hour'),
        ]);
        $this->assertTrue($entity->isExpired());

        $entity2 = new ApiKey([
            'expires_at' => new DateTime('+1 hour'),
        ]);
        $this->assertFalse($entity2->isExpired());

        $entity3 = new ApiKey([
            'expires_at' => null,
        ]);
        $this->assertFalse($entity3->isExpired());
    }

    /**
     * Test write permission includes read
     *
     * @return void
     */
    public function testWritePermissionIncludesRead(): void
    {
        $entity = new ApiKey([
            'permissions' => json_encode(['write']),
        ]);

        $this->assertTrue($entity->hasPermission('read'));
        $this->assertTrue($entity->hasPermission('write'));
        $this->assertFalse($entity->hasPermission('admin'));
    }

    /**
     * Test revoking non-existent key returns false
     *
     * @return void
     */
    public function testRevokeNonExistentKey(): void
    {
        $result = $this->service->revoke(99999);
        $this->assertFalse($result);
    }
}
