<?php
declare(strict_types=1);

namespace App\Model\Table;

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
            ->notEmptyString('channel');

        $validator
            ->scalar('trigger_on')
            ->maxLength('trigger_on', 50)
            ->requirePresence('trigger_on', 'create')
            ->notEmptyString('trigger_on');

        $validator
            ->integer('throttle_minutes')
            ->notEmptyString('throttle_minutes');

        $validator
            ->scalar('recipients')
            ->requirePresence('recipients', 'create')
            ->notEmptyString('recipients');

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
}
