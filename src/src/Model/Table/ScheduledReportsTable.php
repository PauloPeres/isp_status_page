<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ScheduledReports Model
 *
 * Manages scheduled email report configurations per organization.
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 *
 * @method \App\Model\Entity\ScheduledReport newEmptyEntity()
 * @method \App\Model\Entity\ScheduledReport newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ScheduledReport get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ScheduledReport findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ScheduledReport patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ScheduledReport|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ScheduledReport saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ScheduledReportsTable extends Table
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

        $this->setTable('scheduled_reports');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('frequency')
            ->requirePresence('frequency', 'create')
            ->notEmptyString('frequency')
            ->inList('frequency', ['weekly', 'monthly'], 'Frequency must be weekly or monthly.');

        $validator
            ->scalar('recipients')
            ->requirePresence('recipients', 'create')
            ->notEmptyString('recipients');

        $validator
            ->boolean('include_uptime')
            ->allowEmptyString('include_uptime');

        $validator
            ->boolean('include_response_time')
            ->allowEmptyString('include_response_time');

        $validator
            ->boolean('include_incidents')
            ->allowEmptyString('include_incidents');

        $validator
            ->boolean('include_sla')
            ->allowEmptyString('include_sla');

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
     * Find active scheduled reports.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['ScheduledReports.active' => true]);
    }

    /**
     * Find reports that are due to be sent.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findDue(SelectQuery $query): SelectQuery
    {
        return $query->where([
            'ScheduledReports.active' => true,
            'ScheduledReports.next_send_at <=' => new \Cake\I18n\DateTime(),
        ]);
    }
}
