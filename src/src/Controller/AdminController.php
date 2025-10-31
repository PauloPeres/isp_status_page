<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Admin Controller
 *
 * Main dashboard for administrative panel
 */
class AdminController extends AppController
{
    /**
     * Index method - Admin Dashboard
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Get statistics
        $stats = [
            'monitors' => [
                'total' => $this->fetchTable('Monitors')->find()->count(),
                'online' => $this->fetchTable('Monitors')->find()->where(['status' => 'up'])->count(),
                'offline' => $this->fetchTable('Monitors')->find()->where(['status' => 'down'])->count(),
            ],
            'incidents' => [
                'active' => $this->fetchTable('Incidents')->find()->where(['status' => 'investigating'])->count(),
                'resolved_today' => $this->fetchTable('Incidents')
                    ->find()
                    ->where([
                        'status' => 'resolved',
                        'resolved_at >=' => date('Y-m-d 00:00:00')
                    ])
                    ->count(),
            ],
            'subscribers' => [
                'total' => $this->fetchTable('Subscribers')->find()->count(),
                'active' => $this->fetchTable('Subscribers')->find()->where(['active' => true])->count(),
            ],
            'checks' => [
                'total_today' => $this->fetchTable('MonitorChecks')
                    ->find()
                    ->where(['created >=' => date('Y-m-d 00:00:00')])
                    ->count(),
                'failed_today' => $this->fetchTable('MonitorChecks')
                    ->find()
                    ->where([
                        'status' => 'down',
                        'created >=' => date('Y-m-d 00:00:00')
                    ])
                    ->count(),
            ],
        ];

        // Get recent monitors
        $recentMonitors = $this->fetchTable('Monitors')
            ->find()
            ->orderBy(['modified' => 'DESC'])
            ->limit(5)
            ->all();

        // Get recent incidents
        $recentIncidents = $this->fetchTable('Incidents')
            ->find()
            ->orderBy(['created' => 'DESC'])
            ->limit(5)
            ->all();

        $this->set(compact('stats', 'recentMonitors', 'recentIncidents'));
    }
}
