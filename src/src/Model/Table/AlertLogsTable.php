<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AlertLog;
use App\Model\Entity\AlertRule;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AlertLogs Model
 *
 * @property \App\Model\Table\AlertRulesTable&\Cake\ORM\Association\BelongsTo $AlertRules
 * @property \App\Model\Table\IncidentsTable&\Cake\ORM\Association\BelongsTo $Incidents
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 *
 * @method \App\Model\Entity\AlertLog newEmptyEntity()
 * @method \App\Model\Entity\AlertLog newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\AlertLog> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AlertLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AlertLog findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\AlertLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\AlertLog> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AlertLog|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\AlertLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\AlertLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertLog>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlertLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertLog> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlertLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertLog>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AlertLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AlertLog> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AlertLogsTable extends Table
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

        $this->setTable('alert_logs');
        $this->setDisplayField('channel');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('AlertRules', [
            'foreignKey' => 'alert_rule_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Incidents', [
            'foreignKey' => 'incident_id',
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
            ->integer('alert_rule_id')
            ->notEmptyString('alert_rule_id');

        $validator
            ->integer('incident_id')
            ->allowEmptyString('incident_id');

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
            ], 'Invalid alert channel');

        $validator
            ->scalar('recipient')
            ->maxLength('recipient', 255)
            ->requirePresence('recipient', 'create')
            ->notEmptyString('recipient');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status')
            ->inList('status', [
                AlertLog::STATUS_SENT,
                AlertLog::STATUS_FAILED,
                AlertLog::STATUS_QUEUED,
            ], 'Invalid alert log status');

        $validator
            ->dateTime('sent_at')
            ->allowEmptyDateTime('sent_at');

        $validator
            ->scalar('error_message')
            ->allowEmptyString('error_message');

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
        $rules->add($rules->existsIn(['alert_rule_id'], 'AlertRules'), ['errorField' => 'alert_rule_id']);
        $rules->add($rules->existsIn(['incident_id'], 'Incidents'), ['errorField' => 'incident_id']);
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);

        return $rules;
    }

    /**
     * Find alert logs by status
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $status Status to filter by
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByStatus(SelectQuery $query, string $status): SelectQuery
    {
        return $query->where(['AlertLogs.status' => $status]);
    }

    /**
     * Find alert logs by channel
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $channel Channel to filter by
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByChannel(SelectQuery $query, string $channel): SelectQuery
    {
        return $query->where(['AlertLogs.channel' => $channel]);
    }

    /**
     * Find alert logs by monitor
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $monitorId Monitor ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByMonitor(SelectQuery $query, int $monitorId): SelectQuery
    {
        return $query->where(['AlertLogs.monitor_id' => $monitorId]);
    }

    /**
     * Find alert logs by incident
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $incidentId Incident ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByIncident(SelectQuery $query, int $incidentId): SelectQuery
    {
        return $query->where(['AlertLogs.incident_id' => $incidentId]);
    }

    /**
     * Find recent alert logs
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $days Number of days to look back
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findRecent(SelectQuery $query, int $days = 30): SelectQuery
    {
        $since = new DateTime("-{$days} days");

        return $query->where(['AlertLogs.created >=' => $since]);
    }

    /**
     * Get statistics for alert logs
     *
     * @param int|null $monitorId Optional monitor ID to filter by
     * @param int $days Number of days to look back
     * @return array
     */
    public function getStatistics(?int $monitorId = null, int $days = 30): array
    {
        $baseConditions = [];
        $since = new DateTime("-{$days} days");
        $baseConditions['AlertLogs.created >='] = $since;

        if ($monitorId !== null) {
            $baseConditions['monitor_id'] = $monitorId;
        }

        $total = $this->find()->where($baseConditions)->count();
        $sent = $this->find()->where(array_merge($baseConditions, ['status' => AlertLog::STATUS_SENT]))->count();
        $failed = $this->find()->where(array_merge($baseConditions, ['status' => AlertLog::STATUS_FAILED]))->count();
        $queued = $this->find()->where(array_merge($baseConditions, ['status' => AlertLog::STATUS_QUEUED]))->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'queued' => $queued,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Delete old alert logs (retention policy)
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public function deleteOldLogs(int $days = 30): int
    {
        $cutoff = new DateTime("-{$days} days");

        return $this->deleteAll(['created <' => $cutoff]);
    }

    /**
     * Get last alert log for a monitor
     *
     * @param int $monitorId Monitor ID
     * @return \App\Model\Entity\AlertLog|null
     */
    public function getLastLogForMonitor(int $monitorId): ?AlertLog
    {
        return $this->find()
            ->where(['monitor_id' => $monitorId])
            ->orderBy(['created' => 'DESC'])
            ->first();
    }
}
