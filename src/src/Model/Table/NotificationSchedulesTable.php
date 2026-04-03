<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * NotificationSchedules Table (C-05)
 */
class NotificationSchedulesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('notification_schedules');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');
        $this->addBehavior('PublicId');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('organization_id')
            ->notEmptyString('organization_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 200)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('action')
            ->inList('action', ['suppress', 'allow'], 'Action must be "suppress" or "allow".');

        $validator
            ->scalar('start_time')
            ->notEmptyString('start_time');

        $validator
            ->scalar('end_time')
            ->notEmptyString('end_time');

        $validator
            ->scalar('timezone')
            ->notEmptyString('timezone');

        $validator
            ->boolean('active');

        return $validator;
    }
}
