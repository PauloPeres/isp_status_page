<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ChatMessages Model
 *
 * @property \App\Model\Table\ChatConversationsTable&\Cake\ORM\Association\BelongsTo $ChatConversations
 *
 * @method \App\Model\Entity\ChatMessage newEmptyEntity()
 * @method \App\Model\Entity\ChatMessage newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ChatMessage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ChatMessage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ChatMessage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ChatMessagesTable extends Table
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

        $this->setTable('chat_messages');
        $this->setDisplayField('role');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('ChatConversations', [
            'foreignKey' => 'conversation_id',
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
            ->scalar('role')
            ->maxLength('role', 20)
            ->requirePresence('role', 'create')
            ->notEmptyString('role')
            ->inList('role', ['user', 'assistant', 'system']);

        $validator
            ->scalar('content')
            ->allowEmptyString('content');

        $validator
            ->integer('input_tokens')
            ->greaterThanOrEqual('input_tokens', 0);

        $validator
            ->integer('output_tokens')
            ->greaterThanOrEqual('output_tokens', 0);

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
        $rules->add($rules->existsIn(['conversation_id'], 'ChatConversations'), ['errorField' => 'conversation_id']);

        return $rules;
    }
}
