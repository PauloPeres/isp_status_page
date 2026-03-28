<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MonitorCheckDetails Model (TASK-DB-011)
 *
 * Companion table for monitor_checks that stores error_message and details
 * TEXT columns separately. This reduces the main table's heap by ~30%
 * since most checks succeed and have no error data.
 *
 * @property \App\Model\Table\MonitorChecksTable&\Cake\ORM\Association\BelongsTo $MonitorChecks
 *
 * @method \App\Model\Entity\MonitorCheckDetail newEmptyEntity()
 * @method \App\Model\Entity\MonitorCheckDetail newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MonitorCheckDetail> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MonitorCheckDetail get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MonitorCheckDetail|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MonitorCheckDetail>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MonitorCheckDetail>|false saveMany(iterable $entities, array $options = [])
 */
class MonitorCheckDetailsTable extends Table
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

        $this->setTable('monitor_check_details');
        $this->setDisplayField('check_id');
        $this->setPrimaryKey('check_id');

        $this->belongsTo('MonitorChecks', [
            'foreignKey' => 'check_id',
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
            ->notEmptyString('check_id');

        $validator
            ->scalar('error_message')
            ->allowEmptyString('error_message');

        $validator
            ->scalar('details')
            ->allowEmptyString('details');

        return $validator;
    }
}
