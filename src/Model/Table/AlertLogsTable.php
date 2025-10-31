<?php
declare(strict_types=1);

namespace App\Model\Table;

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
            ->notEmptyString('channel');

        $validator
            ->scalar('recipient')
            ->maxLength('recipient', 255)
            ->requirePresence('recipient', 'create')
            ->notEmptyString('recipient');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

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
}
