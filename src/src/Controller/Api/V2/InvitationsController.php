<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

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
}
