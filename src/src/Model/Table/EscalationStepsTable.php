<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\EscalationStep;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * EscalationSteps Model
 *
 * @property \App\Model\Table\EscalationPoliciesTable&\Cake\ORM\Association\BelongsTo $EscalationPolicies
 *
 * @method \App\Model\Entity\EscalationStep newEmptyEntity()
 * @method \App\Model\Entity\EscalationStep newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\EscalationStep> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\EscalationStep get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\EscalationStep findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\EscalationStep patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\EscalationStep> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\EscalationStep|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\EscalationStep saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class EscalationStepsTable extends Table
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

        $this->setTable('escalation_steps');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('EscalationPolicies', [
            'foreignKey' => 'escalation_policy_id',
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
            ->integer('escalation_policy_id')
            ->notEmptyString('escalation_policy_id');

        $validator
            ->integer('step_number')
            ->greaterThan('step_number', 0, __('Step number must be greater than 0'))
            ->requirePresence('step_number', 'create')
            ->notEmptyString('step_number');

        $validator
            ->integer('wait_minutes')
            ->greaterThanOrEqual('wait_minutes', 0, __('Wait time cannot be negative'))
            ->requirePresence('wait_minutes', 'create')
            ->notEmptyString('wait_minutes');

        $validator
            ->scalar('channel')
            ->maxLength('channel', 50)
            ->requirePresence('channel', 'create')
            ->notEmptyString('channel')
            ->inList('channel', EscalationStep::VALID_CHANNELS, __('Invalid alert channel'));

        $validator
            ->scalar('recipients')
            ->requirePresence('recipients', 'create')
            ->notEmptyString('recipients');

        $validator
            ->scalar('message_template')
            ->allowEmptyString('message_template');

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
        $rules->add($rules->existsIn(['escalation_policy_id'], 'EscalationPolicies'), ['errorField' => 'escalation_policy_id']);

        return $rules;
    }
}
