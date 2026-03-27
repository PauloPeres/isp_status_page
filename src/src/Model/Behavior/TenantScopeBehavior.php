<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Tenant\TenantContext;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;

/**
 * TenantScope behavior
 *
 * Automatically scopes all queries, saves, and deletes to the current
 * tenant (organization) as determined by TenantContext.
 */
class TenantScopeBehavior extends Behavior
{
    /**
     * Before find callback.
     *
     * Adds WHERE organization_id = current tenant to all queries.
     * Skips if TenantContext is not set (CLI mode, testing) or if
     * the skipTenantScope option is passed.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param \ArrayObject $options Query options.
     * @param bool $primary Whether this is a primary query.
     * @return void
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        // Skip if no tenant context (CLI, tests, system queries)
        if (!TenantContext::isSet()) {
            return;
        }

        // Skip if explicitly opted out
        if (!empty($options['skipTenantScope'])) {
            return;
        }

        $query->where([$this->_table->getAlias() . '.organization_id' => TenantContext::getCurrentOrgId()]);
    }

    /**
     * Before save callback.
     *
     * Sets organization_id on new entities if not already set.
     * Prevents saving entities that belong to a different tenant.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The entity.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!TenantContext::isSet()) {
            return;
        }

        if ($entity->isNew() && !$entity->has('organization_id')) {
            $entity->set('organization_id', TenantContext::getCurrentOrgId());
        }

        // Security: prevent saving entity to different tenant
        if (!$entity->isNew() && $entity->has('organization_id') && $entity->get('organization_id') !== TenantContext::getCurrentOrgId()) {
            $event->stopPropagation();

            return;
        }
    }

    /**
     * Before delete callback.
     *
     * Prevents deleting entities that belong to a different tenant.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The entity.
     * @param \ArrayObject $options Delete options.
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!TenantContext::isSet()) {
            return;
        }

        if ($entity->get('organization_id') !== TenantContext::getCurrentOrgId()) {
            $event->stopPropagation();

            return;
        }
    }
}
