<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AlertRule;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AlertRules Model
 *
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 * @property \App\Model\Table\AlertLogsTable&\Cake\ORM\Association\HasMany $AlertLogs
 *
 * @method \App\Model\Entity\AlertRule newEmptyEntity()
 * @method \App\Model\Entity\AlertRule newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\AlertRule> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AlertRule get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AlertRule findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\AlertRule patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\AlertRule> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AlertRule|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\AlertRule saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\AlertRule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertRule>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlertRule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertRule> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlertRule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertRule>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlertRule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertRule> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AlertRulesTable extends Table
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

        $this->setTable('alert_rules');
        $this->setDisplayField('channel');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('AlertLogs', [
            'foreignKey' => 'alert_rule_id',
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
            ->scalar('channel')
            ->maxLength('channel', 50)
            ->requirePresence('channel', 'create')
            ->notEmptyString('channel')
            ->inList('channel', [
                AlertRule::CHANNEL_EMAIL,
                AlertRule::CHANNEL_WHATSAPP,
                AlertRule::CHANNEL_TELEGRAM,
                AlertRule::CHANNEL_SMS,
                AlertRule::CHANNEL_PHONE,
            ], __('Invalid alert channel'));

        $validator
            ->scalar('trigger_on')
            ->maxLength('trigger_on', 50)
            ->requirePresence('trigger_on', 'create')
            ->notEmptyString('trigger_on')
            ->inList('trigger_on', [
                AlertRule::TRIGGER_ON_DOWN,
                AlertRule::TRIGGER_ON_UP,
                AlertRule::TRIGGER_ON_DEGRADED,
                AlertRule::TRIGGER_ON_CHANGE,
            ], __('Invalid trigger type'));

        $validator
            ->integer('throttle_minutes')
            ->notEmptyString('throttle_minutes')
            ->greaterThanOrEqual('throttle_minutes', 0, __('Throttle minutes cannot be negative'));

        $validator
            ->scalar('recipients')
            ->requirePresence('recipients', 'create')
            ->notEmptyString('recipients')
            ->add('recipients', 'validJson', [
                'rule' => function ($value) {
                    if (empty($value)) {
                        return false;
                    }
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return false;
                    }

                    return is_array($decoded) && !empty($decoded);
                },
                'message' => __('Recipients must be a valid non-empty JSON array'),
            ]);

        $validator
            ->scalar('template')
            ->allowEmptyString('template');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

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

    /**
     * Find active alert rules
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['AlertRules.active' => true]);
    }

    /**
     * Find alert rules by monitor
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $monitorId Monitor ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByMonitor(SelectQuery $query, int $monitorId): SelectQuery
    {
        return $query->where(['AlertRules.monitor_id' => $monitorId]);
    }

    /**
     * Find alert rules by channel
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $channel Channel name
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByChannel(SelectQuery $query, string $channel): SelectQuery
    {
        return $query->where(['AlertRules.channel' => $channel]);
    }

    /**
     * Get active alert rules for a monitor
     *
     * @param int $monitorId Monitor ID
     * @return array<\App\Model\Entity\AlertRule>
     */
    public function getActiveRulesForMonitor(int $monitorId): array
    {
        return $this->find()
            ->where([
                'AlertRules.monitor_id' => $monitorId,
                'AlertRules.active' => true,
            ])
            ->contain(['Monitors'])
            ->all()
            ->toArray();
    }

    /**
     * Get alert rules that should trigger for a status change
     *
     * @param int $monitorId Monitor ID
     * @param string $oldStatus Old status
     * @param string $newStatus New status
     * @return array<\App\Model\Entity\AlertRule>
     */
    public function getRulesForStatusChange(int $monitorId, string $oldStatus, string $newStatus): array
    {
        $rules = $this->getActiveRulesForMonitor($monitorId);

        return array_filter($rules, function ($rule) use ($oldStatus, $newStatus) {
            return $rule->shouldTrigger($oldStatus, $newStatus);
        });
    }
}
