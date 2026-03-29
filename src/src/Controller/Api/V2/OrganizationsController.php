<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * OrganizationsController (TASK-NG-013)
 *
 * List user's organizations, view current, and switch organization.
 */
class OrganizationsController extends AppController
{
    /**
     * GET /api/v2/organizations
     *
     * List all organizations the current user belongs to.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $orgUsers = $this->fetchTable('OrganizationUsers')->find()
            ->contain(['Organizations'])
            ->where(['OrganizationUsers.user_id' => $this->currentUserId])
            ->all();

        $organizations = [];
        foreach ($orgUsers as $ou) {
            $organizations[] = [
                'id' => $ou->organization_id,
                'name' => $ou->organization->name ?? '',
                'role' => $ou->role,
                'is_current' => ($ou->organization_id === $this->currentOrgId),
            ];
        }

        $this->success(['organizations' => $organizations]);
    }

    /**
     * GET /api/v2/organizations/current
     *
     * Return details of the current organization.
     *
     * @return void
     */
    public function current(): void
    {
        $this->request->allowMethod(['get']);

        $org = $this->fetchTable('Organizations')->find()
            ->where(['Organizations.id' => $this->currentOrgId])
            ->first();

        if (!$org) {
            $this->error('Organization not found', 404);

            return;
        }

        $this->success(['organization' => $org]);
    }

    /**
     * POST /api/v2/organizations/switch
     *
     * Switch to a different organization. Returns a new JWT.
     *
     * @return void
     */
    public function switchOrg(): void
    {
        $this->request->allowMethod(['post']);

        $orgId = (int)$this->request->getData('organization_id');
        if ($orgId <= 0) {
            $this->error('Organization ID is required', 400);

            return;
        }

        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where([
                'OrganizationUsers.user_id' => $this->currentUserId,
                'OrganizationUsers.organization_id' => $orgId,
            ])
            ->first();

        if (!$orgUser && !$this->isSuperAdmin) {
            $this->error('You do not belong to this organization', 403);

            return;
        }

        $role = $orgUser ? $orgUser->role : 'admin';

        $jwtService = new \App\Service\JwtService();
        $accessToken = $jwtService->generateAccessToken(
            $this->currentUserId,
            $orgId,
            $role,
            $this->isSuperAdmin
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
}
