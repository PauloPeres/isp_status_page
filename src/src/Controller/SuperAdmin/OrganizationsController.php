<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

/**
 * Organizations Controller (Super Admin)
 *
 * Provides cross-tenant organization listing, detail views, and impersonation.
 * TASK-SA-009 & TASK-SA-010
 */
class OrganizationsController extends AppController
{
    /**
     * Searchable, filterable, paginated list of all organizations.
     *
     * @return void
     */
    public function index(): void
    {
        $query = $this->fetchTable('Organizations')->find()
            ->contain(['OrganizationUsers']);

        // Search by name or slug
        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where(['OR' => [
                'Organizations.name LIKE' => "%{$search}%",
                'Organizations.slug LIKE' => "%{$search}%",
            ]]);
        }

        // Filter by plan
        $planFilter = $this->request->getQuery('plan');
        if ($planFilter) {
            $query->where(['Organizations.plan' => $planFilter]);
        }

        $query->orderBy(['Organizations.created' => 'DESC']);
        $organizations = $this->paginate($query, ['limit' => 25]);

        // Get monitor counts per org
        $monitorsTable = $this->fetchTable('Monitors');
        $orgMonitorCounts = $monitorsTable->find()
            ->select(['organization_id', 'count' => $monitorsTable->find()->func()->count('*')])
            ->groupBy(['organization_id'])
            ->disableAutoFields()
            ->applyOptions(['skipTenantScope' => true])
            ->all()
            ->combine('organization_id', 'count')
            ->toArray();

        $this->set(compact('organizations', 'search', 'planFilter', 'orgMonitorCounts'));
    }

    /**
     * View detail for a single organization.
     *
     * @param string|null $id Organization ID.
     * @return void
     */
    public function view($id = null): void
    {
        $org = $this->fetchTable('Organizations')->get($id, contain: ['OrganizationUsers' => ['Users']]);

        // Get org's monitors (bypass tenant scope)
        $monitors = $this->fetchTable('Monitors')->find()
            ->where(['Monitors.organization_id' => $id])
            ->applyOptions(['skipTenantScope' => true])
            ->orderBy(['Monitors.name' => 'ASC'])
            ->all();

        // Recent checks
        $recentChecks = $this->fetchTable('MonitorChecks')->find()
            ->where(['MonitorChecks.organization_id' => $id])
            ->applyOptions(['skipTenantScope' => true])
            ->orderBy(['MonitorChecks.checked_at' => 'DESC'])
            ->limit(10)
            ->all();

        // Recent incidents
        $recentIncidents = $this->fetchTable('Incidents')->find()
            ->where(['Incidents.organization_id' => $id])
            ->applyOptions(['skipTenantScope' => true])
            ->orderBy(['Incidents.created' => 'DESC'])
            ->limit(5)
            ->all();

        $this->set(compact('org', 'monitors', 'recentChecks', 'recentIncidents'));
    }

    /**
     * Impersonate an organization — sets session variables so TenantMiddleware
     * resolves to the target org.
     *
     * @param string|null $id Organization ID.
     * @return \Cake\Http\Response|null
     */
    public function impersonate($id = null)
    {
        $this->request->allowMethod(['post']);
        $org = $this->fetchTable('Organizations')->get($id);

        $this->request->getSession()->write('impersonating_org_id', $org->id);
        $this->request->getSession()->write('impersonating_org_name', $org->name);
        $this->request->getSession()->write('current_organization_id', $org->id);

        $this->Flash->success(__('Now impersonating: {0}', $org->name));

        return $this->redirect(['prefix' => false, 'controller' => 'Dashboard', 'action' => 'index']);
    }

    /**
     * Stop impersonating and return to the Super Admin dashboard.
     *
     * @return \Cake\Http\Response|null
     */
    public function stopImpersonation()
    {
        $this->request->getSession()->delete('impersonating_org_id');
        $this->request->getSession()->delete('impersonating_org_name');
        $this->request->getSession()->delete('current_organization_id');

        $this->Flash->success(__('Stopped impersonation'));

        return $this->redirect(['prefix' => 'SuperAdmin', 'controller' => 'Dashboard', 'action' => 'index']);
    }
}
