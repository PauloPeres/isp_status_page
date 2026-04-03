<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Organizations Model
 *
 * @property \App\Model\Table\OrganizationUsersTable&\Cake\ORM\Association\HasMany $OrganizationUsers
 * @property \App\Model\Table\MonitorsTable&\Cake\ORM\Association\HasMany $Monitors
 * @property \App\Model\Table\IncidentsTable&\Cake\ORM\Association\HasMany $Incidents
 * @property \App\Model\Table\IntegrationsTable&\Cake\ORM\Association\HasMany $Integrations
 * @property \App\Model\Table\AlertRulesTable&\Cake\ORM\Association\HasMany $AlertRules
 * @property \App\Model\Table\SubscribersTable&\Cake\ORM\Association\HasMany $Subscribers
 *
 * @method \App\Model\Entity\Organization newEmptyEntity()
 * @method \App\Model\Entity\Organization newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Organization> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Organization get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Organization findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Organization patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Organization> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Organization|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Organization saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class OrganizationsTable extends Table
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

        $this->setTable('organizations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('PublicId');

        $this->hasMany('OrganizationUsers', [
            'foreignKey' => 'organization_id',
        ]);
        $this->hasMany('Monitors', [
            'foreignKey' => 'organization_id',
        ]);
        $this->hasMany('Incidents', [
            'foreignKey' => 'organization_id',
        ]);
        $this->hasMany('Integrations', [
            'foreignKey' => 'organization_id',
        ]);
        $this->hasMany('AlertRules', [
            'foreignKey' => 'organization_id',
        ]);
        $this->hasMany('Subscribers', [
            'foreignKey' => 'organization_id',
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
            ->scalar('slug')
            ->maxLength('slug', 100)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->regex('slug', '/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/', 'Slug must contain only lowercase letters, numbers, and hyphens');

        $validator
            ->scalar('plan')
            ->maxLength('plan', 50)
            ->notEmptyString('plan')
            ->inList('plan', ['free', 'pro', 'business'], 'Invalid plan type');

        $validator
            ->scalar('stripe_customer_id')
            ->maxLength('stripe_customer_id', 255)
            ->allowEmptyString('stripe_customer_id');

        $validator
            ->scalar('stripe_subscription_id')
            ->maxLength('stripe_subscription_id', 255)
            ->allowEmptyString('stripe_subscription_id');

        $validator
            ->dateTime('trial_ends_at')
            ->allowEmptyDateTime('trial_ends_at');

        $validator
            ->scalar('timezone')
            ->maxLength('timezone', 50)
            ->notEmptyString('timezone');

        $validator
            ->scalar('language')
            ->maxLength('language', 10)
            ->notEmptyString('language');

        $validator
            ->scalar('custom_domain')
            ->maxLength('custom_domain', 255)
            ->allowEmptyString('custom_domain');

        $validator
            ->scalar('logo_url')
            ->maxLength('logo_url', 500)
            ->allowEmptyString('logo_url');

        $validator
            ->scalar('settings')
            ->allowEmptyString('settings')
            ->add('settings', 'validJson', [
                'rule' => function ($value) {
                    if (empty($value)) {
                        return true;
                    }
                    json_decode($value);

                    return json_last_error() === JSON_ERROR_NONE;
                },
                'message' => 'Settings must be valid JSON',
            ]);

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
        $rules->add($rules->isUnique(['slug']), ['errorField' => 'slug', 'message' => 'This slug is already in use']);

        return $rules;
    }
}
