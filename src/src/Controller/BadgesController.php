<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BadgeService;

/**
 * Badges Controller
 *
 * Serves public SVG badges for monitor uptime, status, and response time.
 * All endpoints are public (no authentication required).
 */
class BadgesController extends AppController
{
    /**
     * Badge service instance
     *
     * @var \App\Service\BadgeService
     */
    private BadgeService $badgeService;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->badgeService = new BadgeService();
    }

    /**
     * Before filter — allow all badge actions without auth
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['uptime', 'status', 'responseTime']);
    }

    /**
     * Uptime badge — returns SVG badge with uptime percentage
     *
     * @param string $token Monitor badge token
     * @return \Cake\Http\Response
     */
    public function uptime(string $token)
    {
        $monitor = $this->findMonitorByToken($token);
        if (!$monitor) {
            return $this->notFoundBadge();
        }

        $svg = $this->badgeService->generateUptime((int)$monitor->id);

        return $this->svgResponse($svg);
    }

    /**
     * Status badge — returns SVG badge with current status (up/down)
     *
     * @param string $token Monitor badge token
     * @return \Cake\Http\Response
     */
    public function status(string $token)
    {
        $monitor = $this->findMonitorByToken($token);
        if (!$monitor) {
            return $this->notFoundBadge();
        }

        $svg = $this->badgeService->generateStatus((int)$monitor->id);

        return $this->svgResponse($svg);
    }

    /**
     * Response time badge — returns SVG badge with average response time
     *
     * @param string $token Monitor badge token
     * @return \Cake\Http\Response
     */
    public function responseTime(string $token)
    {
        $monitor = $this->findMonitorByToken($token);
        if (!$monitor) {
            return $this->notFoundBadge();
        }

        $svg = $this->badgeService->generateResponseTime((int)$monitor->id);

        return $this->svgResponse($svg);
    }

    /**
     * Find a monitor by its badge token
     *
     * @param string $token The badge token
     * @return \App\Model\Entity\Monitor|null
     */
    private function findMonitorByToken(string $token)
    {
        $monitorsTable = $this->fetchTable('Monitors');

        return $monitorsTable->find()
            ->where(['badge_token' => $token, 'active' => true])
            ->disableAutoFields()
            ->select(['id', 'name', 'status', 'uptime_percentage', 'badge_token'])
            ->enableAutoFields(false)
            ->first();
    }

    /**
     * Return an SVG response
     *
     * @param string $svg SVG content
     * @return \Cake\Http\Response
     */
    private function svgResponse(string $svg): \Cake\Http\Response
    {
        $response = $this->response
            ->withType('image/svg+xml')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0')
            ->withStringBody($svg);

        return $response;
    }

    /**
     * Return a "not found" SVG badge
     *
     * @return \Cake\Http\Response
     */
    private function notFoundBadge(): \Cake\Http\Response
    {
        $svg = $this->badgeService->generateErrorBadge('not found');

        return $this->svgResponse($svg);
    }
}
