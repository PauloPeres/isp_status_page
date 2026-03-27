<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Plans Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\HasMany $Organizations
 *
 * @method \App\Model\Entity\Plan newEmptyEntity()
 * @method \App\Model\Entity\Plan newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Plan> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Plan get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Plan findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Plan patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Plan> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Plan|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Plan saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PlansTable extends Table
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

        $this->setTable('plans');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Organizations', [
            'foreignKey' => 'plan',
            'bindingKey' => 'slug',
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
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 50)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->regex('slug', '/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/', 'Slug must contain only lowercase letters, numbers, and hyphens');

        $validator
            ->scalar('stripe_price_id_monthly')
            ->maxLength('stripe_price_id_monthly', 255)
            ->allowEmptyString('stripe_price_id_monthly');

        $validator
            ->scalar('stripe_price_id_yearly')
            ->maxLength('stripe_price_id_yearly', 255)
            ->allowEmptyString('stripe_price_id_yearly');

        $validator
            ->integer('price_monthly')
            ->greaterThanOrEqual('price_monthly', 0, 'Price must be zero or positive')
            ->requirePresence('price_monthly', 'create')
            ->notEmptyString('price_monthly');

        $validator
            ->integer('price_yearly')
            ->greaterThanOrEqual('price_yearly', 0, 'Price must be zero or positive')
            ->requirePresence('price_yearly', 'create')
            ->notEmptyString('price_yearly');

        $validator
            ->integer('monitor_limit')
            ->requirePresence('monitor_limit', 'create')
            ->notEmptyString('monitor_limit');

        $validator
            ->integer('check_interval_min')
            ->greaterThan('check_interval_min', 0, 'Check interval must be positive')
            ->requirePresence('check_interval_min', 'create')
            ->notEmptyString('check_interval_min');

        $validator
            ->integer('team_member_limit')
            ->requirePresence('team_member_limit', 'create')
            ->notEmptyString('team_member_limit');

        $validator
            ->integer('status_page_limit')
            ->requirePresence('status_page_limit', 'create')
            ->notEmptyString('status_page_limit');

        $validator
            ->integer('api_rate_limit')
            ->greaterThanOrEqual('api_rate_limit', 0, 'API rate limit must be zero or positive')
            ->requirePresence('api_rate_limit', 'create')
            ->notEmptyString('api_rate_limit');

        $validator
            ->integer('data_retention_days')
            ->greaterThan('data_retention_days', 0, 'Data retention must be positive')
            ->requirePresence('data_retention_days', 'create')
            ->notEmptyString('data_retention_days');

        $validator
            ->scalar('features')
            ->allowEmptyString('features')
            ->add('features', 'validJson', [
                'rule' => function ($value) {
                    if (empty($value)) {
                        return true;
                    }
                    json_decode($value);

                    return json_last_error() === JSON_ERROR_NONE;
                },
                'message' => 'Features must be valid JSON',
            ]);

        $validator
            ->integer('display_order')
            ->notEmptyString('display_order');

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

    /**
     * Find plan by slug
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query object
     * @param string $slug The plan slug
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findBySlug(SelectQuery $query, string $slug): SelectQuery
    {
        return $query->where(['Plans.slug' => $slug]);
    }

    /**
     * Find active plans, ordered by display_order
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query
            ->where(['Plans.active' => true])
            ->orderBy(['Plans.display_order' => 'ASC']);
    }
}
