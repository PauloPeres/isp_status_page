<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * NotificationPolicySteps Model
 *
 * @property \App\Model\Table\NotificationPoliciesTable&\Cake\ORM\Association\BelongsTo $NotificationPolicies
 * @property \App\Model\Table\NotificationChannelsTable&\Cake\ORM\Association\BelongsTo $NotificationChannels
 *
 * @method \App\Model\Entity\NotificationPolicyStep newEmptyEntity()
 * @method \App\Model\Entity\NotificationPolicyStep newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\NotificationPolicyStep> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\NotificationPolicyStep get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\NotificationPolicyStep findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\NotificationPolicyStep patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\NotificationPolicyStep> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\NotificationPolicyStep|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\NotificationPolicyStep saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class NotificationPolicyStepsTable extends Table
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

        $this->setTable('notification_policy_steps');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('NotificationPolicies', [
            'foreignKey' => 'notification_policy_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('NotificationChannels', [
            'foreignKey' => 'notification_channel_id',
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
            ->integer('notification_policy_id')
            ->notEmptyString('notification_policy_id');

        $validator
            ->integer('step_order')
            ->greaterThan('step_order', 0, __('Step order must be greater than 0'))
            ->allowEmptyString('step_order');

        $validator
            ->integer('delay_minutes')
            ->greaterThanOrEqual('delay_minutes', 0, __('Delay cannot be negative'))
            ->allowEmptyString('delay_minutes');

        $validator
            ->integer('notification_channel_id')
            ->requirePresence('notification_channel_id', 'create')
            ->notEmptyString('notification_channel_id');

        $validator
            ->boolean('notify_on_resolve')
            ->allowEmptyString('notify_on_resolve');

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
        $rules->add($rules->existsIn(['notification_policy_id'], 'NotificationPolicies'), ['errorField' => 'notification_policy_id']);
        $rules->add($rules->existsIn(['notification_channel_id'], 'NotificationChannels'), ['errorField' => 'notification_channel_id']);

        return $rules;
    }
}
