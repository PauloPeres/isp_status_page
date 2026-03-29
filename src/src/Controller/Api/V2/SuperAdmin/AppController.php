<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use App\Controller\Api\V2\AppController as V2AppController;
use Cake\Event\EventInterface;

/**
 * Super Admin Base Controller (TASK-NG-014)
 *
 * Extends the V2 AppController and enforces super admin access.
 */
class AppController extends V2AppController
{
    /**
     * Before filter — ensure the current user is a super admin.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
        }
    }
}
