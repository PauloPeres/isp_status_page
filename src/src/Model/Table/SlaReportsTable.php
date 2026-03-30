<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SlaReports Model
 *
 * Stores historical SLA compliance reports by period.
 *
 * @property \App\Model\Table\SlaDefinitionsTable&\Cake\ORM\Association\BelongsTo $SlaDefinitions
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 *
 * @method \App\Model\Entity\SlaReport newEmptyEntity()
 * @method \App\Model\Entity\SlaReport newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\SlaReport get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SlaReport findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SlaReport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SlaReport|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SlaReport saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SlaReportsTable extends Table
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

        $this->setTable('sla_reports');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('SlaDefinitions', [
            'foreignKey' => 'sla_definition_id',
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
            ->integer('sla_definition_id')
            ->notEmptyString('sla_definition_id');

        $validator
            ->integer('monitor_id')
            ->notEmptyString('monitor_id');

        $validator
            ->date('period_start')
            ->requirePresence('period_start', 'create')
            ->notEmptyDate('period_start');

        $validator
            ->date('period_end')
            ->requirePresence('period_end', 'create')
            ->notEmptyDate('period_end');

        $validator
            ->scalar('period_type')
            ->notEmptyString('period_type')
            ->inList('period_type', ['monthly', 'quarterly', 'yearly']);

        $validator
            ->decimal('target_uptime')
            ->notEmptyString('target_uptime');

        $validator
            ->decimal('actual_uptime')
            ->notEmptyString('actual_uptime');

        $validator
            ->integer('total_minutes')
            ->notEmptyString('total_minutes');

        $validator
            ->decimal('downtime_minutes')
            ->notEmptyString('downtime_minutes');

        $validator
            ->decimal('allowed_downtime_minutes')
            ->notEmptyString('allowed_downtime_minutes');

        $validator
            ->decimal('remaining_downtime_minutes')
            ->notEmptyString('remaining_downtime_minutes');

        $validator
            ->scalar('status')
            ->notEmptyString('status')
            ->inList('status', ['compliant', 'at_risk', 'breached']);

        $validator
            ->integer('incidents_count')
            ->allowEmptyString('incidents_count');

        return $validator;
    }

    /**
     * Returns a rules checker object.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), ['errorField' => 'organization_id']);
        $rules->add($rules->existsIn(['sla_definition_id'], 'SlaDefinitions'), ['errorField' => 'sla_definition_id']);
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);

        return $rules;
    }

    /**
     * Find reports by SLA definition, ordered by period.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param int $slaDefinitionId SLA definition ID.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByDefinition(SelectQuery $query, int $slaDefinitionId): SelectQuery
    {
        return $query
            ->where(['SlaReports.sla_definition_id' => $slaDefinitionId])
            ->orderBy(['SlaReports.period_start' => 'DESC']);
    }

    /**
     * Find breached reports.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findBreached(SelectQuery $query): SelectQuery
    {
        return $query->where(['SlaReports.status' => 'breached']);
    }
}
