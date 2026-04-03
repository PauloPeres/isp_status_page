<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Text;

/**
 * PublicId behavior
 *
 * Automatically generates a UUID v4 for the `public_id` column on new entities
 * and provides a custom finder for looking up records by their public UUID.
 */
class PublicIdBehavior extends Behavior
{
    /**
     * Before save callback.
     *
     * If the entity is new (or has an empty public_id), generate a UUID v4.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved.
     * @param \ArrayObject $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (empty($entity->get('public_id'))) {
            $entity->set('public_id', Text::uuid());
        }
    }

    /**
     * Finder: look up a record by its public UUID.
     *
     * Usage:
     *   $table->find('byPublicId', publicId: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')->first();
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param string $publicId The UUID to search for.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByPublicId(SelectQuery $query, string $publicId): SelectQuery
    {
        return $query->where([
            $this->_table->getAlias() . '.public_id' => $publicId,
        ]);
    }
}
