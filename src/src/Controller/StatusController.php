<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SettingService;

/**
 * Status Controller
 *
 * Public status page displaying service health
 */
class StatusController extends AppController
{
    /**
     * Setting service instance
     *
     * @var \App\Service\SettingService
     */
    private SettingService $settingService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->settingService = new SettingService();
    }
    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to all status pages
        $this->Authentication->addUnauthenticatedActions(['index', 'history']);
    }

    /**
     * Index method - Main status page
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // Check if status page is public
        $isPublic = $this->settingService->get('status_page_public', true);

        // If not public, require authentication
        if (!$isPublic) {
            $identity = $this->Authentication->getIdentity();
            if (!$identity) {
                $this->Flash->error(__d('status', 'A página de status não está disponível publicamente. Por favor, faça login.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }
        }

        $this->viewBuilder()->setLayout('public');

        // Enable caching based on settings
        $cacheSeconds = (int)$this->settingService->get('status_page_cache_seconds', 30);
        $this->response = $this->response
            ->withCache("-{$cacheSeconds} seconds", "+{$cacheSeconds} seconds")
            ->withHeader('Cache-Control', $isPublic ? "public, max-age={$cacheSeconds}" : "private, max-age={$cacheSeconds}");

        // Get all active monitors
        $monitors = $this->fetchTable('Monitors')
            ->find()
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        // Calculate overall status
        $totalMonitors = $monitors->count();
        $onlineMonitors = 0;
        $offlineMonitors = 0;
        $degradedMonitors = 0;

        foreach ($monitors as $monitor) {
            if ($monitor->status === 'up') {
                $onlineMonitors++;
            } elseif ($monitor->status === 'down') {
                $offlineMonitors++;
            } else {
                $degradedMonitors++;
            }
        }

        // Determine overall system status
        if ($offlineMonitors > 0) {
            if ($offlineMonitors >= $totalMonitors / 2) {
                $systemStatus = 'major-outage';
                $systemMessage = 'Estamos enfrentando problemas graves';
                $systemIcon = '🔴';
            } else {
                $systemStatus = 'partial-outage';
                $systemMessage = 'Alguns serviços estão com problemas';
                $systemIcon = '🟡';
            }
        } elseif ($degradedMonitors > 0) {
            $systemStatus = 'partial-outage';
            $systemMessage = 'Alguns serviços estão degradados';
            $systemIcon = '🟡';
        } else {
            $systemStatus = 'all-systems-operational';
            $systemMessage = 'Todos os sistemas operacionais';
            $systemIcon = '🟢';
        }

        // Get recent incidents (last 7 days)
        $recentIncidents = $this->fetchTable('Incidents')
            ->find()
            ->where([
                'created >=' => date('Y-m-d H:i:s', strtotime('-7 days'))
            ])
            ->orderBy(['created' => 'DESC'])
            ->limit(5)
            ->all();

        // Load settings
        $siteName = $this->settingService->get('site_name', 'ISP Status');
        $statusPageTitle = $this->settingService->get('status_page_title', 'Status dos Serviços');
        $logoUrl = $this->settingService->get('site_logo_url', null);
        $supportEmail = $this->settingService->get('support_email', 'support@example.com');

        // Set HTTP status code based on system status
        if ($systemStatus === 'major-outage') {
            $this->response = $this->response->withStatus(503); // Service Unavailable
        } elseif ($systemStatus === 'partial-outage') {
            $this->response = $this->response->withStatus(500); // Internal Server Error
        }

        $this->set(compact(
            'monitors',
            'systemStatus',
            'systemMessage',
            'systemIcon',
            'totalMonitors',
            'onlineMonitors',
            'offlineMonitors',
            'degradedMonitors',
            'recentIncidents',
            'siteName',
            'statusPageTitle',
            'logoUrl',
            'supportEmail'
        ));
    }

    /**
     * History method - Incident history
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function history()
    {
        // Check if status page is public
        $isPublic = $this->settingService->get('status_page_public', true);

        // If not public, require authentication
        if (!$isPublic) {
            $identity = $this->Authentication->getIdentity();
            if (!$identity) {
                $this->Flash->error(__d('status', 'A página de status não está disponível publicamente. Por favor, faça login.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }
        }

        $this->viewBuilder()->setLayout('public');

        // Get incidents from last 30 days
        $incidents = $this->fetchTable('Incidents')
            ->find()
            ->where([
                'created >=' => date('Y-m-d H:i:s', strtotime('-30 days'))
            ])
            ->orderBy(['created' => 'DESC'])
            ->all();

        // Group incidents by date
        $groupedIncidents = [];
        foreach ($incidents as $incident) {
            $date = $incident->created->format('Y-m-d');
            if (!isset($groupedIncidents[$date])) {
                $groupedIncidents[$date] = [];
            }
            $groupedIncidents[$date][] = $incident;
        }

        // Load settings for footer
        $siteName = $this->settingService->get('site_name', 'ISP Status');
        $supportEmail = $this->settingService->get('support_email', 'support@example.com');

        $this->set(compact('groupedIncidents', 'siteName', 'supportEmail'));
    }
}
