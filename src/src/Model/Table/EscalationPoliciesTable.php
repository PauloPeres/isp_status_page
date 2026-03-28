<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * EscalationPolicies Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\EscalationStepsTable&\Cake\ORM\Association\HasMany $EscalationSteps
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\HasMany $Monitors
 *
 * @method \App\Model\Entity\EscalationPolicy newEmptyEntity()
 * @method \App\Model\Entity\EscalationPolicy newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\EscalationPolicy> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\EscalationPolicy get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\EscalationPolicy findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\EscalationPolicy patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\EscalationPolicy> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\EscalationPolicy|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\EscalationPolicy saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class EscalationPoliciesTable extends Table
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

        $this->setTable('escalation_policies');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('EscalationSteps', [
            'foreignKey' => 'escalation_policy_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'sort' => ['EscalationSteps.step_number' => 'ASC'],
        ]);

        $this->hasMany('Monitors', [
            'foreignKey' => 'escalation_policy_id',
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
            ->boolean('repeat_enabled')
            ->allowEmptyString('repeat_enabled');

        $validator
            ->integer('repeat_after_minutes')
            ->greaterThan('repeat_after_minutes', 0, __('Repeat interval must be greater than 0'))
            ->allowEmptyString('repeat_after_minutes');

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
     * Find active escalation policies.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['EscalationPolicies.active' => true]);
    }
}
