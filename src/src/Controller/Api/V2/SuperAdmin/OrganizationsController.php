<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use App\Service\JwtService;

/**
 * Super Admin OrganizationsController (TASK-NG-014)
 *
 * Manage organizations: list, view, impersonate, grant credits.
 */
class OrganizationsController extends AppController
{
    /**
     * GET /api/v2/super-admin/organizations
     *
     * List all organizations.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('Organizations');
        $query = $table->find()
            ->orderBy(['Organizations.name' => 'ASC']);

        $limit = (int)($this->request->getQuery('limit') ?: 50);
        $limit = min($limit, 200);
        $page = (int)($this->request->getQuery('page') ?: 1);

        $organizations = $query
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        $this->success([
            'organizations' => $organizations->toArray(),
            'pagination' => ['page' => $page, 'limit' => $limit],
        ]);
    }

    /**
     * GET /api/v2/super-admin/organizations/{id}
     *
     * View a single organization with details.
     *
     * @param string $id Organization ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $org = $this->fetchTable('Organizations')->find()
            ->where(['Organizations.id' => $id])
            ->first();

        if (!$org) {
            $this->error('Organization not found', 404);

            return;
        }

        $userCount = $this->fetchTable('OrganizationUsers')->find()
            ->where(['OrganizationUsers.organization_id' => $id])
            ->count();

        $monitorCount = $this->fetchTable('Monitors')->find()
            ->where(['Monitors.organization_id' => $id])
            ->count();

        $this->success([
            'organization' => $org,
            'stats' => [
                'users' => $userCount,
                'monitors' => $monitorCount,
            ],
        ]);
    }

    /**
     * POST /api/v2/super-admin/organizations/{id}/impersonate
     *
     * Generate an access token scoped to the target organization.
     *
     * @param string $id Organization ID.
     * @return void
     */
    public function impersonate(string $id): void
    {
        $this->request->allowMethod(['post']);

        $org = $this->fetchTable('Organizations')->find()
            ->where(['Organizations.id' => $id])
            ->first();

        if (!$org) {
            $this->error('Organization not found', 404);

            return;
        }

        $jwtService = new JwtService();
        $accessToken = $jwtService->generateAccessToken(
            $this->currentUserId,
            (int)$id,
            'admin',
            true
        );

        $this->success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $jwtService->getAccessTokenTtl(),
            'impersonating' => [
                'organization_id' => (int)$id,
                'organization_name' => $org->name,
            ],
        ]);
    }

    /**
     * POST /api/v2/super-admin/organizations/stop-impersonation
     *
     * Generate a fresh token scoped back to the super admin's own organization.
     *
     * @return void
     */
    public function stopImpersonation(): void
    {
        $this->request->allowMethod(['post']);

        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where(['OrganizationUsers.user_id' => $this->currentUserId])
            ->first();

        $orgId = $orgUser ? $orgUser->organization_id : 0;
        $role = $orgUser ? $orgUser->role : 'admin';

        $jwtService = new JwtService();
        $accessToken = $jwtService->generateAccessToken(
            $this->currentUserId,
            $orgId,
            $role,
            true
        );

        $this->success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $jwtService->getAccessTokenTtl(),
            'organization' => [
                'id' => $orgId,
                'role' => $role,
            ],
        ]);
    }

    /**
     * POST /api/v2/super-admin/organizations/{id}/grant-credits
     *
     * Grant credits to an organization.
     *
     * @param string $id Organization ID.
     * @return void
     */
    public function grantCredits(string $id): void
    {
        $this->request->allowMethod(['post']);

        $amount = (int)$this->request->getData('amount');
        $reason = $this->request->getData('reason', '');

        if ($amount <= 0) {
            $this->error('Amount must be positive', 400);

            return;
        }

        $org = $this->fetchTable('Organizations')->find()
            ->where(['Organizations.id' => $id])
            ->first();

        if (!$org) {
            $this->error('Organization not found', 404);

            return;
        }

        try {
            $service = new \App\Service\BillingService();
            $service->grantCredits((int)$id, $amount, $reason, $this->currentUserId);

            $this->success(['message' => "Granted {$amount} credits", 'organization_id' => (int)$id]);
        } catch (\Exception $e) {
            $this->error('Failed to grant credits: ' . $e->getMessage(), 500);
        }
    }
}
