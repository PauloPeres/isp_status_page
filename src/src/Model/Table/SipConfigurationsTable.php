<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\SipConfiguration;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SipConfigurations Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 *
 * @method \App\Model\Entity\SipConfiguration newEmptyEntity()
 * @method \App\Model\Entity\SipConfiguration newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\SipConfiguration> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SipConfiguration get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SipConfiguration findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SipConfiguration patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\SipConfiguration> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SipConfiguration|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SipConfiguration saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SipConfigurationsTable extends Table
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

        $this->setTable('sip_configurations');
        $this->setDisplayField('provider');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('TenantScope');
        $this->addBehavior('PublicId');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
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
            ->notEmptyString('organization_id');

        $validator
            ->scalar('provider')
            ->maxLength('provider', 20)
            ->notEmptyString('provider');

        $validator
            ->scalar('sip_host')
            ->maxLength('sip_host', 255)
            ->allowEmptyString('sip_host');

        $validator
            ->integer('sip_port')
            ->allowEmptyString('sip_port');

        $validator
            ->scalar('sip_username')
            ->maxLength('sip_username', 255)
            ->allowEmptyString('sip_username');

        $validator
            ->scalar('sip_password')
            ->maxLength('sip_password', 255)
            ->allowEmptyString('sip_password');

        $validator
            ->scalar('sip_transport')
            ->maxLength('sip_transport', 10)
            ->allowEmptyString('sip_transport');

        $validator
            ->scalar('caller_id')
            ->maxLength('caller_id', 20)
            ->allowEmptyString('caller_id');

        $validator
            ->scalar('twilio_trunk_sid')
            ->maxLength('twilio_trunk_sid', 64)
            ->allowEmptyString('twilio_trunk_sid');

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
        $rules->add($rules->isUnique(['organization_id'], __('Each organization can have only one SIP configuration.')));

        return $rules;
    }
}
