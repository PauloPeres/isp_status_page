<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Subscriptions Model
 *
 * @property \App\Model\Table\SubscribersTable&\Cake\ORM\Association\BelongsTo $Subscribers
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 *
 * @method \App\Model\Entity\Subscription newEmptyEntity()
 * @method \App\Model\Entity\Subscription newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Subscription> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Subscription get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Subscription findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Subscription patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Subscription> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Subscription|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Subscription saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SubscriptionsTable extends Table
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

        $this->setTable('subscriptions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Subscribers', [
            'foreignKey' => 'subscriber_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
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
            ->integer('subscriber_id')
            ->notEmptyString('subscriber_id');

        $validator
            ->integer('monitor_id')
            ->allowEmptyString('monitor_id');

        $validator
            ->boolean('notify_on_down')
            ->notEmptyString('notify_on_down');

        $validator
            ->boolean('notify_on_up')
            ->notEmptyString('notify_on_up');

        $validator
            ->boolean('notify_on_degraded')
            ->notEmptyString('notify_on_degraded');

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
        $rules->add($rules->existsIn(['subscriber_id'], 'Subscribers'), ['errorField' => 'subscriber_id']);
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);

        return $rules;
    }
}
