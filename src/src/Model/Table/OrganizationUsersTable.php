<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * OrganizationUsers Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\OrganizationUser newEmptyEntity()
 * @method \App\Model\Entity\OrganizationUser newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\OrganizationUser> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationUser get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\OrganizationUser findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\OrganizationUser patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\OrganizationUser> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationUser|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\OrganizationUser saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrganizationUsersTable extends Table
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

        $this->setTable('organization_users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

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
            ->integer('organization_id')
            ->requirePresence('organization_id', 'create')
            ->notEmptyString('organization_id');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('role')
            ->maxLength('role', 20)
            ->notEmptyString('role')
            ->inList('role', ['owner', 'admin', 'member', 'viewer'], 'Invalid role. Must be: owner, admin, member, or viewer');

        $validator
            ->integer('invited_by')
            ->allowEmptyString('invited_by');

        $validator
            ->dateTime('invited_at')
            ->allowEmptyDateTime('invited_at');

        $validator
            ->dateTime('accepted_at')
            ->allowEmptyDateTime('accepted_at');

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
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), [
            'errorField' => 'organization_id',
            'message' => 'Invalid organization',
        ]);
        $rules->add($rules->existsIn(['user_id'], 'Users'), [
            'errorField' => 'user_id',
            'message' => 'Invalid user',
        ]);
        $rules->add($rules->isUnique(['organization_id', 'user_id']), [
            'errorField' => 'user_id',
            'message' => 'This user is already a member of this organization',
        ]);

        return $rules;
    }
}
