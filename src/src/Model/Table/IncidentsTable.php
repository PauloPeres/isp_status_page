<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Incidents Model
 *
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\BelongsTo $Monitors
 * @property \App\Model\Table\AlertLogsTable&\Cake\ORM\Association\HasMany $AlertLogs
 *
 * @method \App\Model\Entity\Incident newEmptyEntity()
 * @method \App\Model\Entity\Incident newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Incident> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Incident get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Incident findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Incident patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Incident> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Incident|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Incident saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Incident>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Incident>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Incident>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Incident> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Incident>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Incident>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Incident>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Incident> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class IncidentsTable extends Table
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

        $this->setTable('incidents');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Monitors', [
            'foreignKey' => 'monitor_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('AlertLogs', [
            'foreignKey' => 'incident_id',
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->scalar('severity')
            ->maxLength('severity', 20)
            ->requirePresence('severity', 'create')
            ->notEmptyString('severity');

        $validator
            ->dateTime('started_at')
            ->requirePresence('started_at', 'create')
            ->notEmptyDateTime('started_at');

        $validator
            ->dateTime('identified_at')
            ->allowEmptyDateTime('identified_at');

        $validator
            ->dateTime('resolved_at')
            ->allowEmptyDateTime('resolved_at');

        $validator
            ->integer('duration')
            ->allowEmptyString('duration');

        $validator
            ->boolean('auto_created')
            ->notEmptyString('auto_created');

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
