<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\VoiceCallLog;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * VoiceCallLogs Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\IncidentsTable&\Cake\ORM\Association\BelongsTo $Incidents
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 * @property \App\Model\Table\NotificationChannelsTable&\Cake\ORM\Association\BelongsTo $NotificationChannels
 *
 * @method \App\Model\Entity\VoiceCallLog newEmptyEntity()
 * @method \App\Model\Entity\VoiceCallLog newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\VoiceCallLog> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\VoiceCallLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\VoiceCallLog findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\VoiceCallLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\VoiceCallLog> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\VoiceCallLog|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\VoiceCallLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VoiceCallLogsTable extends Table
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

        $this->setTable('voice_call_logs');
        $this->setDisplayField('phone_number');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');
        $this->addBehavior('PublicId');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('Incidents', [
            'foreignKey' => 'incident_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('NotificationChannels', [
            'foreignKey' => 'notification_channel_id',
            'joinType' => 'LEFT',
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
            ->integer('incident_id')
            ->notEmptyString('incident_id');

        $validator
            ->integer('monitor_id')
            ->notEmptyString('monitor_id');

        $validator
            ->integer('notification_channel_id')
            ->allowEmptyString('notification_channel_id');

        $validator
            ->scalar('phone_number')
            ->maxLength('phone_number', 20)
            ->requirePresence('phone_number', 'create')
            ->notEmptyString('phone_number');

        $validator
            ->scalar('call_sid')
            ->maxLength('call_sid', 64)
            ->allowEmptyString('call_sid');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->notEmptyString('status')
            ->inList('status', VoiceCallLog::VALID_STATUSES, __('Invalid call status'));

        $validator
            ->scalar('dtmf_input')
            ->maxLength('dtmf_input', 5)
            ->allowEmptyString('dtmf_input');

        $validator
            ->integer('duration_seconds')
            ->allowEmptyString('duration_seconds');

        $validator
            ->scalar('tts_language')
            ->maxLength('tts_language', 10)
            ->notEmptyString('tts_language');

        $validator
            ->scalar('tts_message')
            ->allowEmptyString('tts_message');

        $validator
            ->integer('cost_credits')
            ->notEmptyString('cost_credits');

        $validator
            ->scalar('sip_provider')
            ->maxLength('sip_provider', 20)
            ->notEmptyString('sip_provider');

        $validator
            ->integer('escalation_position')
            ->notEmptyString('escalation_position');

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
        $rules->add($rules->existsIn(['incident_id'], 'Incidents'), ['errorField' => 'incident_id']);
        $rules->add($rules->existsIn(['monitor_id'], 'Monitors'), ['errorField' => 'monitor_id']);

        return $rules;
    }

    /**
     * Find calls by incident.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $incidentId Incident ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByIncident(SelectQuery $query, int $incidentId): SelectQuery
    {
        return $query->where(['VoiceCallLogs.incident_id' => $incidentId]);
    }

    /**
     * Find calls by call SID.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $callSid The Twilio Call SID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByCallSid(SelectQuery $query, string $callSid): SelectQuery
    {
        return $query->where(['VoiceCallLogs.call_sid' => $callSid]);
    }

    /**
     * Find active (non-terminal) calls for an incident.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $incidentId Incident ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActiveForIncident(SelectQuery $query, int $incidentId): SelectQuery
    {
        return $query->where([
            'VoiceCallLogs.incident_id' => $incidentId,
            'VoiceCallLogs.status IN' => [
                VoiceCallLog::STATUS_INITIATED,
                VoiceCallLog::STATUS_RINGING,
                VoiceCallLog::STATUS_ANSWERED,
            ],
        ]);
    }
}
