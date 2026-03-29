<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Settings Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class SettingsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/settings');
    }

    public function save()
    {
        return $this->redirect('/app/settings');
    }

    public function reset()
    {
        return $this->redirect('/app/settings');
    }

    public function saveChannels()
    {
        return $this->redirect('/app/settings');
    }

    public function testNotificationChannel()
    {
        return $this->redirect('/app/settings');
    }
}
