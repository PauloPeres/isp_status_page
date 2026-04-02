<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Invitation;
use App\Model\Table\InvitationsTable;
use App\Model\Table\OrganizationUsersTable;
use App\Model\Table\UsersTable;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Core\Configure;
use Cake\Routing\Router;

/**
 * Invitation Service
 *
 * Handles team invitation lifecycle: sending, accepting, and revoking invitations.
 */
class InvitationService
{
    use LocatorAwareTrait;

    /**
     * Invitations table instance.
     *
     * @var \App\Model\Table\InvitationsTable
     */
    private InvitationsTable $Invitations;

    /**
     * OrganizationUsers table instance.
     *
     * @var \App\Model\Table\OrganizationUsersTable
     */
    private OrganizationUsersTable $OrganizationUsers;

    /**
     * Users table instance.
     *
     * @var \App\Model\Table\UsersTable
     */
    private UsersTable $Users;

    /**
     * Invitation expiry duration in days.
     */
    private const EXPIRY_DAYS = 7;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Invitations = $this->fetchTable('Invitations');
        $this->OrganizationUsers = $this->fetchTable('OrganizationUsers');
        $this->Users = $this->fetchTable('Users');
    }

    /**
     * Send a team invitation.
     *
     * Creates an invitation record with a unique token, sets expiry,
     * and sends an invitation email.
     *
     * @param int $orgId Organization ID.
     * @param string $email Invitee's email address.
     * @param string $role Role to assign on acceptance.
     * @param int $invitedBy User ID of the person sending the invitation.
     * @return \App\Model\Entity\Invitation The created invitation.
     * @throws \RuntimeException If the invitation could not be saved.
     */
    public function send(int $orgId, string $email, string $role, int $invitedBy): Invitation
    {
        // Check if there's already a pending invitation for this email in this org
        $existing = $this->Invitations->find()
            ->where([
                'Invitations.organization_id' => $orgId,
                'Invitations.email' => $email,
                'Invitations.accepted_at IS' => null,
                'Invitations.expires_at >' => new DateTime(),
            ])
            ->first();

        if ($existing) {
            throw new \RuntimeException(__('A pending invitation already exists for this email address.'));
        }

        // Check if the user is already a member
        $existingUser = $this->Users->find()
            ->where(['Users.email' => $email])
            ->first();

        if ($existingUser) {
            $existingMember = $this->OrganizationUsers->find()
                ->where([
                    'OrganizationUsers.organization_id' => $orgId,
                    'OrganizationUsers.user_id' => $existingUser->id,
                ])
                ->first();

            if ($existingMember) {
                throw new \RuntimeException(__('This user is already a member of this organization.'));
            }
        }

        $token = bin2hex(random_bytes(32));

        $invitation = $this->Invitations->newEntity([
            'organization_id' => $orgId,
            'email' => $email,
            'role' => $role,
            'token' => $token,
            'invited_by' => $invitedBy,
            'expires_at' => new DateTime('+' . self::EXPIRY_DAYS . ' days'),
        ]);

        $saved = $this->Invitations->save($invitation);

        if (!$saved) {
            Log::error('Failed to save invitation', [
                'organization_id' => $orgId,
                'email' => $email,
                'errors' => $invitation->getErrors(),
            ]);

            throw new \RuntimeException(__('Failed to create invitation.'));
        }

        // Send invitation email
        $this->sendInvitationEmail($saved);

        return $saved;
    }

    /**
     * Accept an invitation by token.
     *
     * Finds the invitation, creates a user if needed, creates the
     * OrganizationUser record, and marks the invitation as accepted.
     *
     * @param string $token The invitation token.
     * @return bool True if the invitation was successfully accepted.
     */
    public function accept(string $token): bool
    {
        $invitation = $this->Invitations->find('byToken', token: $token)
            ->contain(['Organizations'])
            ->first();

        if (!$invitation) {
            Log::warning('Invitation not found for token', ['token' => substr($token, 0, 8) . '...']);

            return false;
        }

        if ($invitation->isAccepted()) {
            Log::info('Invitation already accepted', ['id' => $invitation->id]);

            return false;
        }

        if ($this->isExpired($invitation)) {
            Log::info('Invitation expired', ['id' => $invitation->id]);

            return false;
        }

        // Find or create the user
        $user = $this->Users->find()
            ->where(['Users.email' => $invitation->email])
            ->first();

        if (!$user) {
            // Create a new user with a random password (they'll need to set it)
            $tempPassword = bin2hex(random_bytes(16));
            $user = $this->Users->newEntity([
                'username' => explode('@', $invitation->email)[0] . '_' . substr(bin2hex(random_bytes(4)), 0, 6),
                'email' => $invitation->email,
                'password' => $tempPassword,
                'role' => 'user',
                'active' => true,
                'organization_id' => $invitation->organization_id,
                'email_verified' => true,
            ]);

            $user = $this->Users->save($user);

            if (!$user) {
                Log::error('Failed to create user for invitation', [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                ]);

                return false;
            }
        }

        // Check if user is already a member
        $existingMember = $this->OrganizationUsers->find()
            ->where([
                'OrganizationUsers.organization_id' => $invitation->organization_id,
                'OrganizationUsers.user_id' => $user->id,
            ])
            ->first();

        if (!$existingMember) {
            $orgUser = $this->OrganizationUsers->newEntity([
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'invited_by' => $invitation->invited_by,
                'invited_at' => $invitation->created,
                'accepted_at' => new DateTime(),
            ]);

            if (!$this->OrganizationUsers->save($orgUser)) {
                Log::error('Failed to create organization user for invitation', [
                    'invitation_id' => $invitation->id,
                    'user_id' => $user->id,
                    'errors' => $orgUser->getErrors(),
                ]);

                return false;
            }
        }

        // Mark invitation as accepted
        $invitation->accepted_at = new DateTime();

        return (bool)$this->Invitations->save($invitation);
    }

    /**
     * Revoke a pending invitation.
     *
     * @param int $invitationId The invitation ID to revoke.
     * @return bool True if revoked successfully.
     */
    public function revoke(int $invitationId): bool
    {
        $invitation = $this->Invitations->find()
            ->where(['Invitations.id' => $invitationId])
            ->first();

        if (!$invitation) {
            return false;
        }

        if ($invitation->isAccepted()) {
            return false;
        }

        return (bool)$this->Invitations->delete($invitation);
    }

    /**
     * Check if an invitation has expired.
     *
     * @param \App\Model\Entity\Invitation $invitation The invitation to check.
     * @return bool True if expired.
     */
    public function isExpired(Invitation $invitation): bool
    {
        return $invitation->isExpired();
    }

    /**
     * Send the invitation email.
     *
     * @param \App\Model\Entity\Invitation $invitation The invitation.
     * @return void
     */
    private function sendInvitationEmail(Invitation $invitation): void
    {
        try {
            $invitation = $this->Invitations->find()
                ->where(['Invitations.id' => $invitation->id])
                ->contain(['Organizations', 'Inviter'])
                ->first();

            $acceptUrl = Router::url([
                'controller' => 'Invitations',
                'action' => 'accept',
                $invitation->token,
            ], true);

            $orgName = $invitation->organization->name ?? 'the organization';
            $inviterName = $invitation->inviter->username ?? 'a team member';

            $settingService = new SettingService();

            $mailer = new Mailer();
            $mailer->setTransport('default')
                ->setFrom([
                    $settingService->get('smtp_from_email', Configure::read('Brand.noreplyEmail', 'noreply@usekeeup.com')) => $settingService->get('smtp_from_name', Configure::read('Brand.emailFromName', 'KeepUp')),
                ])
                ->setTo($invitation->email)
                ->setSubject(__('You\'ve been invited to join {0}', $orgName))
                ->setViewVars([
                    'invitation' => $invitation,
                    'acceptUrl' => $acceptUrl,
                    'orgName' => $orgName,
                    'inviterName' => $inviterName,
                ])
                ->viewBuilder()
                ->setTemplate('team_invite')
                ->setLayout('default');

            $mailer->deliver();
        } catch (\Exception $e) {
            Log::error('Failed to send invitation email: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ]);
        }
    }
}
