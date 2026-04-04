<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ChatConversations Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\ChatMessagesTable&\Cake\ORM\Association\HasMany $ChatMessages
 *
 * @method \App\Model\Entity\ChatConversation newEmptyEntity()
 * @method \App\Model\Entity\ChatConversation newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ChatConversation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ChatConversation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ChatConversation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ChatConversationsTable extends Table
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

        $this->setTable('chat_conversations');
        $this->setDisplayField('title');
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
            'joinType' => 'INNER',
        ]);
        $this->hasMany('ChatMessages', [
            'foreignKey' => 'conversation_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->notEmptyString('title');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->inList('status', ['active', 'archived']);

        $validator
            ->integer('message_count')
            ->greaterThanOrEqual('message_count', 0);

        $validator
            ->integer('input_tokens_used')
            ->greaterThanOrEqual('input_tokens_used', 0);

        $validator
            ->integer('output_tokens_used')
            ->greaterThanOrEqual('output_tokens_used', 0);

        return $validator;
    }

    /**
     * Returns a rules checker object.
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
     * Finder for active conversations.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['ChatConversations.status' => 'active']);
    }

    /**
     * Finder for conversations by user.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param int $userId The user ID to filter by.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByUser(SelectQuery $query, int $userId): SelectQuery
    {
        return $query->where(['ChatConversations.user_id' => $userId]);
    }
}
