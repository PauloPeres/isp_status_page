<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MonitorChecks Model
 *
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 *
 * @method \App\Model\Entity\MonitorCheck newEmptyEntity()
 * @method \App\Model\Entity\MonitorCheck newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MonitorCheck> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MonitorCheck get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MonitorCheck findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MonitorCheck patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MonitorCheck> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MonitorCheck|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MonitorCheck saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MonitorCheck>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MonitorCheck>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MonitorCheck>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MonitorCheck> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MonitorCheck>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MonitorCheck>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MonitorCheck>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MonitorCheck> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MonitorChecksTable extends Table
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

        $this->setTable('monitor_checks');
        $this->setDisplayField('status');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
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
            ->notEmptyString('monitor_id');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->integer('response_time')
            ->allowEmptyString('response_time');

        $validator
            ->integer('status_code')
            ->allowEmptyString('status_code');

        $validator
            ->scalar('error_message')
            ->allowEmptyString('error_message');

        $validator
            ->scalar('details')
            ->allowEmptyString('details');

        $validator
            ->dateTime('checked_at')
            ->requirePresence('checked_at', 'create')
            ->notEmptyDateTime('checked_at');

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

        return $rules;
    }
}
