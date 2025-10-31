<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Subscribers Model
 *
 * @property \App\Model\Table\SubscriptionsTable&\Cake\ORM\Association\HasMany $Subscriptions
 *
 * @method \App\Model\Entity\Subscriber newEmptyEntity()
 * @method \App\Model\Entity\Subscriber newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Subscriber> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Subscriber get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Subscriber findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Subscriber patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Subscriber> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Subscriber|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Subscriber saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Subscriber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscriber>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscriber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscriber> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscriber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscriber>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscriber>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscriber> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SubscribersTable extends Table
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

        $this->setTable('subscribers');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Subscriptions', [
            'foreignKey' => 'subscriber_id',
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
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->allowEmptyString('name');

        $validator
            ->scalar('verification_token')
            ->maxLength('verification_token', 255)
            ->allowEmptyString('verification_token');

        $validator
            ->boolean('verified')
            ->notEmptyString('verified');

        $validator
            ->dateTime('verified_at')
            ->allowEmptyDateTime('verified_at');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        $validator
            ->scalar('unsubscribe_token')
            ->maxLength('unsubscribe_token', 255)
            ->allowEmptyString('unsubscribe_token');

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
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }
}
