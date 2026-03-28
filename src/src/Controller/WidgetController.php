<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Widget Controller
 *
 * Provides embeddable status widgets for external sites (P3-012).
 * Public, no authentication required.
 */
class WidgetController extends AppController
{
    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // All widget actions are public
        $this->Authentication->addUnauthenticatedActions(['status', 'statusJs']);
    }

    /**
     * Status widget - returns a small HTML page showing current system status.
     * Can be embedded via iframe.
     *
     * @param string|null $slug The organization/status page slug.
     * @return \Cake\Http\Response|null|void
     */
    public function status($slug = null)
    {
        $statusPagesTable = $this->fetchTable('StatusPages');

        $statusPage = $statusPagesTable->find()
            ->where(['slug' => $slug, 'active' => true])
            ->first();

        if ($statusPage === null) {
            $this->response = $this->response
                ->withType('html')
                ->withStringBody('<div style="font-family:sans-serif;padding:8px 16px;border-radius:8px;background:#f5f5f5;display:inline-block;"><span style="color:#999;">Status page not found</span></div>');

            return $this->response;
        }

        // Load monitors
        $monitorIds = $statusPage->getMonitorIds();
        $monitors = [];
        if (!empty($monitorIds)) {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitors = $monitorsTable->find()
                ->where(['id IN' => $monitorIds, 'active' => true])
                ->all()
                ->toArray();
        }

        // Calculate overall status
        $allUp = true;
        $anyDown = false;
        foreach ($monitors as $monitor) {
            if ($monitor->status === 'down') {
                $anyDown = true;
                $allUp = false;
            } elseif ($monitor->status !== 'up') {
                $allUp = false;
            }
        }

        if (empty($monitors)) {
            $statusLabel = __('No monitors configured');
            $statusColor = '#999';
            $bgColor = '#f5f5f5';
        } elseif ($allUp) {
            $statusLabel = __('All Systems Operational');
            $statusColor = '#2E7D32';
            $bgColor = '#E8F5E9';
        } elseif ($anyDown) {
            $statusLabel = __('Some Systems Are Down');
            $statusColor = '#C62828';
            $bgColor = '#FFEBEE';
        } else {
            $statusLabel = __('Some Systems Are Degraded');
            $statusColor = '#F57F17';
            $bgColor = '#FFF8E1';
        }

        // Apply custom primary color if set
        $themeConfig = $statusPage->getThemeConfig();

        $this->viewBuilder()->setLayout(false);
        $this->set(compact('statusPage', 'statusLabel', 'statusColor', 'bgColor', 'monitors'));
    }

    /**
     * Status JS embed - returns a JavaScript snippet that injects the widget.
     *
     * @param string|null $slug The organization/status page slug.
     * @return \Cake\Http\Response
     */
    public function statusJs($slug = null)
    {
        $baseUrl = $this->request->scheme() . '://' . $this->request->host();
        $widgetUrl = $baseUrl . '/widget/status/' . urlencode((string)$slug);

        $js = <<<JS
(function() {
    var iframe = document.createElement('iframe');
    iframe.src = '{$widgetUrl}';
    iframe.style.border = 'none';
    iframe.style.width = '320px';
    iframe.style.height = '60px';
    iframe.style.overflow = 'hidden';
    iframe.title = 'System Status';
    document.currentScript.parentNode.insertBefore(iframe, document.currentScript);
})();
JS;

        $this->response = $this->response
            ->withType('application/javascript')
            ->withHeader('Cache-Control', 'public, max-age=60')
            ->withStringBody($js);

        return $this->response;
    }
}
