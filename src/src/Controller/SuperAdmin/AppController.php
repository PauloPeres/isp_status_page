<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

use App\Controller\AppController as BaseAppController;
use App\Tenant\TenantContext;

class AppController extends BaseAppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setLayout('super_admin');
        TenantContext::reset();
    }

    /**
     * Helper to query without tenant scope.
     *
     * @param string $alias The table alias.
     * @return \Cake\ORM\Table
     */
    protected function fetchTableAll(string $alias)
    {
        return $this->fetchTable($alias);
    }
}
