<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ApiKeys Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\ApiKey newEmptyEntity()
 * @method \App\Model\Entity\ApiKey newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ApiKey> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ApiKey get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ApiKey findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ApiKey patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ApiKey> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ApiKey|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ApiKey saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ApiKey>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ApiKey>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ApiKey>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ApiKey> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ApiKey>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ApiKey>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ApiKey>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ApiKey> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ApiKeysTable extends Table
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

        $this->setTable('api_keys');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('key_hash')
            ->maxLength('key_hash', 255)
            ->requirePresence('key_hash', 'create')
            ->notEmptyString('key_hash');

        $validator
            ->scalar('key_prefix')
            ->maxLength('key_prefix', 12)
            ->requirePresence('key_prefix', 'create')
            ->notEmptyString('key_prefix');

        $validator
            ->scalar('permissions')
            ->allowEmptyString('permissions');

        $validator
            ->integer('rate_limit')
            ->notEmptyString('rate_limit');

        $validator
            ->dateTime('last_used_at')
            ->allowEmptyDateTime('last_used_at');

        $validator
            ->dateTime('expires_at')
            ->allowEmptyDateTime('expires_at');

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
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), ['errorField' => 'organization_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    /**
     * Find active API keys
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['ApiKeys.active' => true]);
    }

    /**
     * Find API key by prefix
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $prefix Key prefix
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByPrefix(SelectQuery $query, string $prefix): SelectQuery
    {
        return $query->where(['ApiKeys.key_prefix' => $prefix]);
    }
}
