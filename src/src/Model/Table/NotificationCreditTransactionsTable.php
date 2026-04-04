<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * NotificationCreditTransactions Model
 *
 * Tracks all credit movements: usage, purchases, monthly grants,
 * manual adjustments, and refunds.
 */
class NotificationCreditTransactionsTable extends Table
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

        $this->setTable('notification_credit_transactions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);
        $this->addBehavior('TenantScope');

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
            ->scalar('type')
            ->maxLength('type', 20)
            ->notEmptyString('type')
            ->inList('type', ['usage', 'purchase', 'monthly_grant', 'manual_adjustment', 'refund', 'auto_replenish']);

        $validator
            ->integer('amount')
            ->notEmptyString('amount');

        $validator
            ->integer('balance_after')
            ->notEmptyString('balance_after');

        $validator
            ->scalar('channel')
            ->maxLength('channel', 50)
            ->allowEmptyString('channel');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('reference_id')
            ->maxLength('reference_id', 100)
            ->allowEmptyString('reference_id');

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
}
