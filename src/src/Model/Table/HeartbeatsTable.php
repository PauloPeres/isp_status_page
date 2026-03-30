<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Heartbeats Model
 *
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 *
 * @method \App\Model\Entity\Heartbeat newEmptyEntity()
 * @method \App\Model\Entity\Heartbeat newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Heartbeat get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Heartbeat findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Heartbeat|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Heartbeat saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class HeartbeatsTable extends Table
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

        $this->setTable('heartbeats');
        $this->setDisplayField('token');
        $this->setPrimaryKey('id');

        $this->addBehavior('TenantScope');

        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
            'joinType' => 'INNER',
        ]);
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
            ->integer('monitor_id')
            ->requirePresence('monitor_id', 'create')
            ->notEmptyString('monitor_id');

        $validator
            ->integer('organization_id')
            ->requirePresence('organization_id', 'create')
            ->notEmptyString('organization_id');

        $validator
            ->scalar('token')
            ->maxLength('token', 64)
            ->requirePresence('token', 'create')
            ->notEmptyString('token');

        $validator
            ->dateTime('last_ping_at')
            ->allowEmptyDateTime('last_ping_at');

        $validator
            ->integer('expected_interval')
            ->notEmptyString('expected_interval')
            ->greaterThan('expected_interval', 0, __('Expected interval must be greater than 0'));

        $validator
            ->integer('grace_period')
            ->allowEmptyString('grace_period')
            ->greaterThanOrEqual('grace_period', 0, __('Grace period cannot be negative'));

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
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), ['errorField' => 'organization_id']);
        $rules->add($rules->isUnique(['token']), ['errorField' => 'token']);

        return $rules;
    }
}
