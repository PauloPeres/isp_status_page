<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Monitors Controller
 *
 * Redirects all legacy admin routes to the Angular SPA.
 * The Angular app uses API v2 endpoints for all monitor operations.
 *
 * @property \App\Model\Table\MonitorsTable $Monitors
 */
class MonitorsController extends AppController
{
    /**
     * Index - redirect to Angular monitors list
     *
     * @return \Cake\Http\Response
     */
    public function index()
    {
        return $this->redirect('/app/monitors');
    }

    /**
     * View - redirect to Angular monitor detail
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response
     */
    public function view($id = null)
    {
        return $this->redirect('/app/monitors/' . $id);
    }

    /**
     * Add - redirect to Angular monitor creation
     *
     * @return \Cake\Http\Response
     */
    public function add()
    {
        return $this->redirect('/app/monitors/new');
    }

    /**
     * Edit - redirect to Angular monitor edit
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response
     */
    public function edit($id = null)
    {
        return $this->redirect('/app/monitors/' . $id . '/edit');
    }

    /**
     * Delete - redirect to Angular monitors list
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response
     */
    public function delete($id = null)
    {
        return $this->redirect('/app/monitors');
    }

    /**
     * Toggle - redirect to Angular monitors list
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response
     */
    public function toggle($id = null)
    {
        return $this->redirect('/app/monitors');
    }

    /**
     * Test connection - redirect to Angular
     *
     * @param string|null $id Monitor id.
     * @return \Cake\Http\Response
     */
    public function testConnection($id = null)
    {
        return $this->redirect('/app/monitors/' . $id);
    }

    /**
     * Bulk action - redirect to Angular monitors list
     *
     * @return \Cake\Http\Response
     */
    public function bulkAction()
    {
        return $this->redirect('/app/monitors');
    }

    /**
     * Import - redirect to Angular monitors list
     *
     * @return \Cake\Http\Response
     */
    public function import()
    {
        return $this->redirect('/app/monitors');
    }
}
