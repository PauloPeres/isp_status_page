<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Invitation;
use App\Service\InvitationService;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * InvitationService Test Case
 */
class InvitationServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Invitations',
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Plans',
        'app.Monitors',
    ];

    /**
     * @var \App\Service\InvitationService
     */
    protected InvitationService $invitationService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->invitationService = new InvitationService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->invitationService);
        parent::tearDown();
    }

    /**
     * Test sending a valid invitation creates a record.
     */
    public function testSendCreatesInvitation(): void
    {
        $invitation = $this->invitationService->send(1, 'brand-new@example.com', 'member', 1);

        $this->assertInstanceOf(Invitation::class, $invitation);
        $this->assertEquals(1, $invitation->organization_id);
        $this->assertEquals('brand-new@example.com', $invitation->email);
        $this->assertEquals('member', $invitation->role);
        $this->assertEquals(1, $invitation->invited_by);
        $this->assertNotEmpty($invitation->token);
        $this->assertEquals(64, strlen($invitation->token));
        $this->assertNull($invitation->accepted_at);
        $this->assertNotNull($invitation->expires_at);
    }

    /**
     * Test sending a duplicate invitation throws exception.
     */
    public function testSendDuplicatePendingInvitationThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        // Fixture ID 1 is a pending invitation for 'newmember@example.com' in org 1
        $this->invitationService->send(1, 'newmember@example.com', 'member', 1);
    }

    /**
     * Test sending invitation to existing org member throws exception.
     */
    public function testSendToExistingMemberThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        // User ID 1 (admin@example.com) is already a member of org 1
        $this->invitationService->send(1, 'admin@example.com', 'member', 1);
    }

    /**
     * Test accepting a valid invitation.
     */
    public function testAcceptValidInvitation(): void
    {
        // Fixture ID 1: pending, not expired, for 'newmember@example.com'
        $token = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

        $result = $this->invitationService->accept($token);

        $this->assertTrue($result);

        // Verify the invitation is marked accepted
        $invitationsTable = TableRegistry::getTableLocator()->get('Invitations');
        $invitation = $invitationsTable->get(1);
        $this->assertNotNull($invitation->accepted_at);

        // Verify organization user was created
        $orgUsersTable = TableRegistry::getTableLocator()->get('OrganizationUsers');
        $usersTable = TableRegistry::getTableLocator()->get('Users');

        $user = $usersTable->find()
            ->where(['Users.email' => 'newmember@example.com'])
            ->first();

        $this->assertNotNull($user, 'User should have been created');

        $orgUser = $orgUsersTable->find()
            ->where([
                'OrganizationUsers.organization_id' => 1,
                'OrganizationUsers.user_id' => $user->id,
            ])
            ->first();

        $this->assertNotNull($orgUser, 'Organization user should have been created');
        $this->assertEquals('member', $orgUser->role);
    }

    /**
     * Test accepting an already-accepted invitation returns false.
     */
    public function testAcceptAlreadyAcceptedReturnsFalse(): void
    {
        // Fixture ID 2: already accepted
        $token = 'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3';

        $result = $this->invitationService->accept($token);

        $this->assertFalse($result);
    }

    /**
     * Test accepting an expired invitation returns false.
     */
    public function testAcceptExpiredInvitationReturnsFalse(): void
    {
        // Fixture ID 3: expired
        $token = 'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4';

        $result = $this->invitationService->accept($token);

        $this->assertFalse($result);
    }

    /**
     * Test accepting with non-existent token returns false.
     */
    public function testAcceptNonExistentTokenReturnsFalse(): void
    {
        $result = $this->invitationService->accept('0000000000000000000000000000000000000000000000000000000000000000');

        $this->assertFalse($result);
    }

    /**
     * Test revoking a pending invitation.
     */
    public function testRevokePendingInvitation(): void
    {
        $result = $this->invitationService->revoke(1);

        $this->assertTrue($result);

        // Verify it's deleted
        $invitationsTable = TableRegistry::getTableLocator()->get('Invitations');
        $count = $invitationsTable->find()
            ->where(['Invitations.id' => 1])
            ->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Test revoking an already-accepted invitation returns false.
     */
    public function testRevokeAcceptedInvitationReturnsFalse(): void
    {
        // Fixture ID 2 is already accepted
        $result = $this->invitationService->revoke(2);

        $this->assertFalse($result);
    }

    /**
     * Test revoking a non-existent invitation returns false.
     */
    public function testRevokeNonExistentReturnsFalse(): void
    {
        $result = $this->invitationService->revoke(999);

        $this->assertFalse($result);
    }

    /**
     * Test isExpired on expired invitation.
     */
    public function testIsExpiredReturnsTrueForExpired(): void
    {
        $invitation = new Invitation();
        $invitation->expires_at = new DateTime('-1 day');

        $this->assertTrue($this->invitationService->isExpired($invitation));
    }

    /**
     * Test isExpired on valid invitation.
     */
    public function testIsExpiredReturnsFalseForValid(): void
    {
        $invitation = new Invitation();
        $invitation->expires_at = new DateTime('+7 days');

        $this->assertFalse($this->invitationService->isExpired($invitation));
    }
}
