<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MonitorChecksRollup Model
 *
 * Stores aggregated monitor check data in 5-minute, 1-hour, and 1-day windows.
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 *
 * @method \App\Model\Entity\MonitorChecksRollup newEmptyEntity()
 * @method \App\Model\Entity\MonitorChecksRollup newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MonitorChecksRollup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MonitorChecksRollup findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MonitorChecksRollup|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MonitorChecksRollup saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class MonitorChecksRollupTable extends Table
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

        $this->setTable('monitor_checks_rollup');
        $this->setDisplayField('period_type');
        $this->setPrimaryKey('id');

        $this->addBehavior('TenantScope');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);
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
            ->integer('organization_id')
            ->notEmptyString('organization_id');

        $validator
            ->integer('monitor_id')
            ->notEmptyString('monitor_id');

        $validator
            ->dateTime('period_start')
            ->requirePresence('period_start', 'create')
            ->notEmptyDateTime('period_start');

        $validator
            ->dateTime('period_end')
            ->requirePresence('period_end', 'create')
            ->notEmptyDateTime('period_end');

        $validator
            ->scalar('period_type')
            ->maxLength('period_type', 10)
            ->requirePresence('period_type', 'create')
            ->notEmptyString('period_type')
            ->inList('period_type', ['5min', '1hour', '1day'], 'Invalid period type');

        $validator
            ->integer('check_count')
            ->notEmptyString('check_count');

        $validator
            ->integer('success_count')
            ->notEmptyString('success_count');

        $validator
            ->integer('failure_count')
            ->notEmptyString('failure_count');

        $validator
            ->integer('timeout_count')
            ->notEmptyString('timeout_count');

        $validator
            ->integer('error_count')
            ->notEmptyString('error_count');

        $validator
            ->decimal('avg_response_time')
            ->allowEmptyString('avg_response_time');

        $validator
            ->integer('min_response_time')
            ->allowEmptyString('min_response_time');

        $validator
            ->integer('max_response_time')
            ->allowEmptyString('max_response_time');

        $validator
            ->decimal('uptime_percentage')
            ->allowEmptyString('uptime_percentage');

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
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);

        return $rules;
    }

    /**
     * Find rollups by monitor and period type
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query object
     * @param int $monitorId The monitor ID
     * @param string $periodType The period type (5min, 1hour, 1day)
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByMonitorAndPeriod(SelectQuery $query, int $monitorId, string $periodType): SelectQuery
    {
        return $query
            ->where([
                'MonitorChecksRollup.monitor_id' => $monitorId,
                'MonitorChecksRollup.period_type' => $periodType,
            ])
            ->orderBy(['MonitorChecksRollup.period_start' => 'DESC']);
    }
}
