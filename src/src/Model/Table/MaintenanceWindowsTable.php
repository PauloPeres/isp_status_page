<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MaintenanceWindows Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 *
 * @method \App\Model\Entity\MaintenanceWindow newEmptyEntity()
 * @method \App\Model\Entity\MaintenanceWindow newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MaintenanceWindow get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MaintenanceWindow|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MaintenanceWindowsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('maintenance_windows');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');
        $this->addBehavior('PublicId');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('monitor_ids')
            ->allowEmptyString('monitor_ids');

        $validator
            ->dateTime('starts_at')
            ->requirePresence('starts_at', 'create')
            ->notEmptyDateTime('starts_at');

        $validator
            ->dateTime('ends_at')
            ->requirePresence('ends_at', 'create')
            ->notEmptyDateTime('ends_at');

        $validator
            ->boolean('auto_suppress_alerts')
            ->allowEmptyString('auto_suppress_alerts');

        $validator
            ->boolean('notify_subscribers')
            ->allowEmptyString('notify_subscribers');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->inList('status', ['scheduled', 'in_progress', 'completed', 'cancelled']);

        $validator->boolean('is_recurring')->allowEmptyString('is_recurring');
        $validator->scalar('recurrence_pattern')
            ->allowEmptyString('recurrence_pattern')
            ->inList('recurrence_pattern', ['daily', 'weekly', 'biweekly', 'monthly'], 'Invalid recurrence pattern.');
        $validator->scalar('recurrence_days')->allowEmptyString('recurrence_days');
        $validator->scalar('recurrence_time_start')->maxLength('recurrence_time_start', 5)->allowEmptyString('recurrence_time_start');
        $validator->scalar('recurrence_time_end')->maxLength('recurrence_time_end', 5)->allowEmptyString('recurrence_time_end');
        $validator->date('effective_from')->allowEmptyDate('effective_from');
        $validator->date('recurrence_end_date')->allowEmptyDate('recurrence_end_date');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), ['errorField' => 'organization_id']);

        return $rules;
    }
}
