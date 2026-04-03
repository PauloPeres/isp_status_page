<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CheckRegions Model
 *
 * @property \App\Model\Table\MonitorChecksTable&\Cake\ORM\Association\HasMany $MonitorChecks
 *
 * @method \App\Model\Entity\CheckRegion newEmptyEntity()
 * @method \App\Model\Entity\CheckRegion newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CheckRegion|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CheckRegion saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class CheckRegionsTable extends Table
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

        $this->setTable('check_regions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('PublicId');

        $this->hasMany('MonitorChecks', [
            'foreignKey' => 'region_id',
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
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('code')
            ->maxLength('code', 20)
            ->requirePresence('code', 'create')
            ->notEmptyString('code');

        $validator
            ->scalar('endpoint_url')
            ->maxLength('endpoint_url', 500)
            ->allowEmptyString('endpoint_url');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

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
        $rules->add($rules->isUnique(['code']), ['errorField' => 'code']);

        return $rules;
    }

    /**
     * Find active regions.
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['CheckRegions.active' => true]);
    }
}
