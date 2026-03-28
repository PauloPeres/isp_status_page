<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Feed Controller
 *
 * Provides RSS feeds for public consumption.
 */
class FeedController extends AppController
{
    /**
     * Before filter — allow public access to feed actions
     *
     * @param \Cake\Event\EventInterface $event The event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['incidents']);
    }

    /**
     * Incidents RSS feed
     *
     * Returns the 20 most recent incidents as RSS 2.0 XML.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function incidents()
    {
        $incidents = $this->fetchTable('Incidents')->find()
            ->contain(['Monitors'])
            ->orderBy(['Incidents.created' => 'DESC'])
            ->limit(20)
            ->all();

        $siteName = (new \App\Service\SettingService())->get('site_name', 'ISP Status');
        $siteUrl = (new \App\Service\SettingService())->get('site_url', '');

        $this->set(compact('incidents', 'siteName', 'siteUrl'));
        $this->viewBuilder()->disableAutoLayout();
        $this->response = $this->response->withType('application/rss+xml');
    }
}
