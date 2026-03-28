<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * NotificationCredits Model
 *
 * Tracks credit balances for paid notification channels per organization.
 */
class NotificationCreditsTable extends Table
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

        $this->setTable('notification_credits');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->integer('balance')
            ->notEmptyString('balance');

        $validator
            ->integer('monthly_grant')
            ->notEmptyString('monthly_grant');

        $validator
            ->boolean('auto_recharge');

        $validator
            ->integer('auto_recharge_threshold')
            ->notEmptyString('auto_recharge_threshold');

        $validator
            ->integer('auto_recharge_amount')
            ->notEmptyString('auto_recharge_amount');

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
        $rules->add($rules->isUnique(['organization_id']), [
            'errorField' => 'organization_id',
            'message' => 'Credit record already exists for this organization',
        ]);

        return $rules;
    }
}
