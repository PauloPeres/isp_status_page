<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Invitations Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Inviter
 *
 * @method \App\Model\Entity\Invitation newEmptyEntity()
 * @method \App\Model\Entity\Invitation newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Invitation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Invitation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Invitation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class InvitationsTable extends Table
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

        $this->setTable('invitations');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Inviter', [
            'className' => 'Users',
            'foreignKey' => 'invited_by',
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
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('role')
            ->maxLength('role', 20)
            ->notEmptyString('role')
            ->inList('role', ['owner', 'admin', 'member', 'viewer'], 'Invalid role');

        $validator
            ->scalar('token')
            ->maxLength('token', 64)
            ->requirePresence('token', 'create')
            ->notEmptyString('token');

        $validator
            ->integer('invited_by')
            ->requirePresence('invited_by', 'create')
            ->notEmptyString('invited_by');

        $validator
            ->dateTime('accepted_at')
            ->allowEmptyDateTime('accepted_at');

        $validator
            ->dateTime('expires_at')
            ->requirePresence('expires_at', 'create')
            ->notEmptyDateTime('expires_at');

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
        $rules->add($rules->isUnique(['token']), [
            'errorField' => 'token',
            'message' => 'This token is already in use',
        ]);

        return $rules;
    }

    /**
     * Find pending invitations (not accepted, not expired).
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findPending($query): \Cake\ORM\Query\SelectQuery
    {
        return $query->where([
            'Invitations.accepted_at IS' => null,
            'Invitations.expires_at >' => new \Cake\I18n\DateTime(),
        ]);
    }

    /**
     * Find invitation by token.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param string $token The invitation token.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByToken($query, string $token): \Cake\ORM\Query\SelectQuery
    {
        return $query->where(['Invitations.token' => $token]);
    }
}
