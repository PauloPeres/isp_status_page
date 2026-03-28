<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * IncidentUpdates Model
 *
 * Stores timeline updates for incidents. Each update represents a status
 * change, team member comment, or system-generated event in the incident lifecycle.
 *
 * @property \App\Model\Table\IncidentsTable&\Cake\ORM\Association\BelongsTo $Incidents
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\IncidentUpdate newEmptyEntity()
 * @method \App\Model\Entity\IncidentUpdate newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\IncidentUpdate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\IncidentUpdate findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\IncidentUpdate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\IncidentUpdate|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\IncidentUpdate saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class IncidentUpdatesTable extends Table
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

        $this->setTable('incident_updates');
        $this->setDisplayField('status');
        $this->setPrimaryKey('id');

        $this->addBehavior('TenantScope');

        $this->belongsTo('Incidents', [
            'foreignKey' => 'incident_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
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
            ->integer('incident_id')
            ->notEmptyString('incident_id');

        $validator
            ->integer('organization_id')
            ->notEmptyString('organization_id');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->scalar('status')
            ->maxLength('status', 30)
            ->requirePresence('status', 'create')
            ->notEmptyString('status')
            ->inList('status', [
                'investigating', 'identified', 'monitoring', 'resolved', 'update',
            ], 'Invalid status value.');

        $validator
            ->scalar('message')
            ->requirePresence('message', 'create')
            ->notEmptyString('message');

        $validator
            ->boolean('is_public')
            ->allowEmptyString('is_public');

        $validator
            ->scalar('source')
            ->maxLength('source', 20)
            ->allowEmptyString('source')
            ->inList('source', [
                'web', 'api', 'system', 'email', 'telegram', 'sms',
            ], 'Invalid source value.');

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
        $rules->add($rules->existsIn(['incident_id'], 'Incidents'), ['errorField' => 'incident_id']);
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), ['errorField' => 'organization_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), [
            'errorField' => 'user_id',
            'allowNullableNulls' => true,
        ]);

        return $rules;
    }

    /**
     * Find public updates (visible on status page)
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findPublic(SelectQuery $query): SelectQuery
    {
        return $query->where(['IncidentUpdates.is_public' => true]);
    }

    /**
     * Find updates for a specific incident
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query to modify.
     * @param int $incidentId Incident ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByIncident(SelectQuery $query, int $incidentId): SelectQuery
    {
        return $query
            ->where(['IncidentUpdates.incident_id' => $incidentId])
            ->orderBy(['IncidentUpdates.created' => 'ASC']);
    }
}
