<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\AuditLogService;
use App\Service\PlanService;

/**
 * InvitationsController (TASK-NG-010)
 *
 * Send, list, and revoke organization invitations.
 */
class InvitationsController extends AppController
{
    /**
     * GET /api/v2/invitations
     *
     * List pending invitations for the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Invitations');
        $invitations = $table->find()
            ->where(['Invitations.organization_id' => $this->currentOrgId])
            ->orderBy(['Invitations.created' => 'DESC'])
            ->all();

        $this->success(['invitations' => $invitations->toArray()]);
    }

    /**
     * POST /api/v2/invitations
     *
     * Send a new invitation.
     *
     * @return void
     */
    public function send(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $planService = new PlanService();
        $check = $planService->checkLimit($this->currentOrgId, 'team_member');
        if (!$check['allowed']) {
            $this->planLimitError("Team member limit reached. Your {$check['plan_name']} plan allows {$check['limit']} team members.", $check);
            return;
        }

        $email = $this->request->getData('email');
        $role = $this->request->getData('role', 'member');

        if (empty($email)) {
            $this->error('Email is required', 400);

            return;
        }

        try {
            $service = new \App\Service\InvitationService();
            $invitation = $service->send($this->currentOrgId, $email, $role, $this->currentUserId);

            $audit = new AuditLogService();
            $audit->log(
                'user_invited',
                $this->currentUserId,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['email' => $email, 'role' => $role, 'invitation_id' => $invitation->id ?? null],
                $this->currentOrgId ?: null
            );

            $this->success(['invitation' => $invitation], 201);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/v2/invitations/{id}
     *
     * Revoke a pending invitation.
     *
     * @param string $id Invitation ID.
     * @return void
     */
    public function revoke(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Invitations');
        $invitation = $table->find()
            ->where([
                'Invitations.id' => $id,
                'Invitations.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$invitation) {
            $this->error('Invitation not found', 404);

            return;
        }

        if (!$table->delete($invitation)) {
            $this->error('Failed to revoke invitation', 500);

            return;
        }

        $this->success(['message' => 'Invitation revoked']);
    }

    /**
     * POST /api/v2/invitations/{id}/resend
     *
     * Resend a pending invitation email.
     *
     * @param string $id Invitation ID.
     * @return void
     */
    public function resend(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Invitations');
        $invitation = $table->find()
            ->where([
                'Invitations.id' => $id,
                'Invitations.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$invitation) {
            $this->error('Invitation not found', 404);
            return;
        }

        if ($invitation->accepted_at) {
            $this->error('This invitation has already been accepted', 422);
            return;
        }

        // Regenerate token and extend expiry
        $invitation->set('token', bin2hex(random_bytes(32)));
        $invitation->set('expires_at', new \Cake\I18n\DateTime('+7 days'));
        $invitation->set('modified', new \Cake\I18n\DateTime());

        if ($table->save($invitation)) {
            // TODO: send email notification
            $this->success(['message' => 'Invitation resent', 'invitation' => $invitation]);
        } else {
            $this->error('Failed to resend invitation', 500);
        }
    }
}
