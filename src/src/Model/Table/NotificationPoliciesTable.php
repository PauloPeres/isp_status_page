<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\NotificationPolicy;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * NotificationPolicies Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\NotificationPolicyStepsTable&\Cake\ORM\Association\HasMany $NotificationPolicySteps
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\HasMany $Monitors
 *
 * @method \App\Model\Entity\NotificationPolicy newEmptyEntity()
 * @method \App\Model\Entity\NotificationPolicy newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\NotificationPolicy> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\NotificationPolicy get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\NotificationPolicy findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\NotificationPolicy patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\NotificationPolicy> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\NotificationPolicy|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\NotificationPolicy saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class NotificationPoliciesTable extends Table
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

        $this->setTable('notification_policies');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('NotificationPolicySteps', [
            'foreignKey' => 'notification_policy_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'sort' => ['NotificationPolicySteps.step_order' => 'ASC'],
        ]);

        $this->hasMany('Monitors', [
            'foreignKey' => 'notification_policy_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('trigger_type')
            ->maxLength('trigger_type', 20)
            ->notEmptyString('trigger_type')
            ->inList('trigger_type', NotificationPolicy::VALID_TRIGGER_TYPES, __('Invalid trigger type'));

        $validator
            ->integer('repeat_interval_minutes')
            ->greaterThanOrEqual('repeat_interval_minutes', 0, __('Repeat interval cannot be negative'))
            ->allowEmptyString('repeat_interval_minutes');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

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

    /**
     * Find active notification policies.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['NotificationPolicies.active' => true]);
    }
}
