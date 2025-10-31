<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * IntegrationLogs Model
 *
 * @property \App\Model\Table\IntegrationsTable&\Cake\ORM\Association\BelongsTo $Integrations
 *
 * @method \App\Model\Entity\IntegrationLog newEmptyEntity()
 * @method \App\Model\Entity\IntegrationLog newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\IntegrationLog> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\IntegrationLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\IntegrationLog findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\IntegrationLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\IntegrationLog> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\IntegrationLog|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\IntegrationLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\IntegrationLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\IntegrationLog>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\IntegrationLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\IntegrationLog> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\IntegrationLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\IntegrationLog>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\IntegrationLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\IntegrationLog> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class IntegrationLogsTable extends Table
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

        $this->setTable('integration_logs');
        $this->setDisplayField('action');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Integrations', [
            'foreignKey' => 'integration_id',
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
            ->integer('integration_id')
            ->notEmptyString('integration_id');

        $validator
            ->scalar('action')
            ->maxLength('action', 100)
            ->requirePresence('action', 'create')
            ->notEmptyString('action');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->scalar('message')
            ->allowEmptyString('message');

        $validator
            ->scalar('details')
            ->allowEmptyString('details');

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
        $rules->add($rules->existsIn(['integration_id'], 'Integrations'), ['errorField' => 'integration_id']);

        return $rules;
    }
}
