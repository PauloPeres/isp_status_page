<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * WebhookDeliveries Model
 *
 * @property \App\Model\Table\WebhookEndpointsTable&\Cake\ORM\Association\BelongsTo $WebhookEndpoints
 *
 * @method \App\Model\Entity\WebhookDelivery newEmptyEntity()
 * @method \App\Model\Entity\WebhookDelivery newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\WebhookDelivery|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WebhookDelivery saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class WebhookDeliveriesTable extends Table
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

        $this->setTable('webhook_deliveries');
        $this->setDisplayField('event_type');
        $this->setPrimaryKey('id');

        $this->belongsTo('WebhookEndpoints', [
            'foreignKey' => 'webhook_endpoint_id',
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
            ->scalar('event_type')
            ->maxLength('event_type', 100)
            ->requirePresence('event_type', 'create')
            ->notEmptyString('event_type');

        $validator
            ->scalar('payload')
            ->requirePresence('payload', 'create')
            ->notEmptyString('payload');

        $validator
            ->integer('response_code')
            ->allowEmptyString('response_code');

        $validator
            ->scalar('response_body')
            ->allowEmptyString('response_body');

        $validator
            ->integer('attempts')
            ->notEmptyString('attempts');

        $validator
            ->dateTime('delivered_at')
            ->allowEmptyDateTime('delivered_at');

        $validator
            ->dateTime('next_retry_at')
            ->allowEmptyDateTime('next_retry_at');

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
        $rules->add($rules->existsIn(['webhook_endpoint_id'], 'WebhookEndpoints'), ['errorField' => 'webhook_endpoint_id']);

        return $rules;
    }

    /**
     * Find deliveries pending retry.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findPendingRetry(SelectQuery $query): SelectQuery
    {
        return $query
            ->where([
                'WebhookDeliveries.delivered_at IS' => null,
                'WebhookDeliveries.attempts <' => 5,
                'OR' => [
                    'WebhookDeliveries.next_retry_at IS' => null,
                    'WebhookDeliveries.next_retry_at <=' => new \Cake\I18n\DateTime(),
                ],
            ]);
    }
}
