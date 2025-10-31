<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Integrations Model
 *
 * @property \App\Model\Table\IntegrationLogsTable&\Cake\ORM\Association\HasMany $IntegrationLogs
 *
 * @method \App\Model\Entity\Integration newEmptyEntity()
 * @method \App\Model\Entity\Integration newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Integration> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Integration get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Integration findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Integration patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Integration> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Integration|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Integration saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Integration>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Integration>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Integration>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Integration> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Integration>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Integration>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Integration>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Integration> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class IntegrationsTable extends Table
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

        $this->setTable('integrations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('IntegrationLogs', [
            'foreignKey' => 'integration_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('type')
            ->maxLength('type', 50)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('configuration')
            ->requirePresence('configuration', 'create')
            ->notEmptyString('configuration');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        $validator
            ->dateTime('last_sync_at')
            ->allowEmptyDateTime('last_sync_at');

        $validator
            ->scalar('last_sync_status')
            ->maxLength('last_sync_status', 20)
            ->allowEmptyString('last_sync_status');

        return $validator;
    }
}
