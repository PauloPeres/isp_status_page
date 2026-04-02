<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\NotificationChannel;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * NotificationChannels Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\NotificationPolicyStepsTable&\Cake\ORM\Association\HasMany $NotificationPolicySteps
 *
 * @method \App\Model\Entity\NotificationChannel newEmptyEntity()
 * @method \App\Model\Entity\NotificationChannel newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\NotificationChannel> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\NotificationChannel get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\NotificationChannel findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\NotificationChannel patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\NotificationChannel> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\NotificationChannel|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\NotificationChannel saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class NotificationChannelsTable extends Table
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

        $this->setTable('notification_channels');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');

        // Override schema type so CakePHP marshaller properly handles array <-> JSON
        $this->getSchema()->setColumnType('configuration', 'json');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('NotificationPolicySteps', [
            'foreignKey' => 'notification_channel_id',
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
            ->notEmptyString('type')
            ->inList('type', NotificationChannel::VALID_TYPES, __('Invalid channel type'));

        $validator
            ->requirePresence('configuration', 'create')
            ->notEmptyArray('configuration');

        $validator
            ->boolean('active')
            ->allowEmptyString('active');

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

        return $rules;
    }

    /**
     * Find active notification channels.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['NotificationChannels.active' => true]);
    }
}
