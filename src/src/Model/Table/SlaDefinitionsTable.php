<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SlaDefinitions Model
 *
 * Manages SLA definitions. Each monitor can have at most one SLA definition.
 *
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 * @property \App\Model\Table\SlaReportsTable&\Cake\ORM\Association\HasMany $SlaReports
 *
 * @method \App\Model\Entity\SlaDefinition newEmptyEntity()
 * @method \App\Model\Entity\SlaDefinition newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\SlaDefinition get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SlaDefinition findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SlaDefinition patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SlaDefinition|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SlaDefinition saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SlaDefinitionsTable extends Table
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

        $this->setTable('sla_definitions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SlaReports', [
            'foreignKey' => 'sla_definition_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
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
            ->requirePresence('monitor_id', 'create')
            ->notEmptyString('monitor_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->decimal('target_uptime')
            ->requirePresence('target_uptime', 'create')
            ->notEmptyString('target_uptime')
            ->range('target_uptime', [90, 100], 'Target uptime must be between 90% and 100%.');

        $validator
            ->scalar('measurement_period')
            ->requirePresence('measurement_period', 'create')
            ->notEmptyString('measurement_period')
            ->inList('measurement_period', ['monthly', 'quarterly', 'yearly'], 'Invalid measurement period.');

        $validator
            ->boolean('breach_notification')
            ->allowEmptyString('breach_notification');

        $validator
            ->decimal('warning_threshold')
            ->allowEmptyString('warning_threshold')
            ->add('warning_threshold', 'validRange', [
                'rule' => function ($value, $context) {
                    $val = (float)$value;
                    if ($val < 90 || $val > 100) {
                        return 'Warning threshold must be between 90% and 100%.';
                    }
                    // Warning threshold must be >= target uptime
                    $target = (float)($context['data']['target_uptime'] ?? 99.9);
                    if ($val < $target) {
                        return 'Warning threshold must be greater than or equal to target uptime.';
                    }

                    return true;
                },
            ]);

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
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);
        $rules->add($rules->isUnique(['monitor_id'], 'This monitor already has an SLA definition.'));

        return $rules;
    }

    /**
     * Find active SLA definitions.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['SlaDefinitions.active' => true]);
    }

    /**
     * Find SLA definition for a specific monitor.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param int $monitorId Monitor ID.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByMonitor(SelectQuery $query, int $monitorId): SelectQuery
    {
        return $query->where(['SlaDefinitions.monitor_id' => $monitorId]);
    }
}
