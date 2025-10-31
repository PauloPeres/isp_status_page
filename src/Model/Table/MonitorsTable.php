<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Monitors Model
 *
 * @property \App\Model\Table\AlertLogsTable&\Cake\ORM\Association\HasMany $AlertLogs
 * @property \App\Model\Table\AlertRulesTable&\Cake\ORM\Association\HasMany $AlertRules
 * @property \App\Model\Table\IncidentsTable&\Cake\ORM\Association\HasMany $Incidents
 * @property \App\Model\Table\MonitorChecksTable&\Cake\ORM\Association\HasMany $MonitorChecks
 * @property \App\Model\Table\SubscriptionsTable&\Cake\ORM\Association\HasMany $Subscriptions
 *
 * @method \App\Model\Entity\Monitor newEmptyEntity()
 * @method \App\Model\Entity\Monitor newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Monitor> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Monitor get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Monitor findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Monitor patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Monitor> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Monitor|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Monitor saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Monitor>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Monitor>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Monitor>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Monitor> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Monitor>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Monitor>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Monitor>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Monitor> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MonitorsTable extends Table
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

        $this->setTable('monitors');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('AlertLogs', [
            'foreignKey' => 'monitor_id',
        ]);
        $this->hasMany('AlertRules', [
            'foreignKey' => 'monitor_id',
        ]);
        $this->hasMany('Incidents', [
            'foreignKey' => 'monitor_id',
        ]);
        $this->hasMany('MonitorChecks', [
            'foreignKey' => 'monitor_id',
        ]);
        $this->hasMany('Subscriptions', [
            'foreignKey' => 'monitor_id',
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
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('type')
            ->maxLength('type', 50)
            ->requirePresence('type', 'create')
            ->notEmptyString('type')
            ->inList('type', ['http', 'ping', 'port', 'api', 'ixc', 'zabbix'], 'Invalid monitor type');

        $validator
            ->scalar('configuration')
            ->allowEmptyString('configuration')
            ->add('configuration', 'validJson', [
                'rule' => function ($value) {
                    if (empty($value)) {
                        return true;
                    }
                    json_decode($value);

                    return json_last_error() === JSON_ERROR_NONE;
                },
                'message' => 'Configuration must be valid JSON',
            ]);

        $validator
            ->integer('check_interval')
            ->notEmptyString('check_interval')
            ->greaterThan('check_interval', 0, 'Check interval must be greater than 0');

        $validator
            ->integer('timeout')
            ->notEmptyString('timeout')
            ->greaterThan('timeout', 0, 'Timeout must be greater than 0');

        $validator
            ->integer('retry_count')
            ->notEmptyString('retry_count')
            ->greaterThanOrEqual('retry_count', 0, 'Retry count cannot be negative');

        $validator
            ->scalar('status')
            ->maxLength('status', 20)
            ->notEmptyString('status')
            ->inList('status', ['up', 'down', 'degraded', 'unknown'], 'Invalid status');

        $validator
            ->dateTime('last_check_at')
            ->allowEmptyDateTime('last_check_at');

        $validator
            ->dateTime('next_check_at')
            ->allowEmptyDateTime('next_check_at');

        $validator
            ->decimal('uptime_percentage')
            ->allowEmptyString('uptime_percentage')
            ->range('uptime_percentage', [0, 100], 'Uptime percentage must be between 0 and 100');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        $validator
            ->boolean('visible_on_status_page')
            ->notEmptyString('visible_on_status_page');

        $validator
            ->integer('display_order')
            ->notEmptyString('display_order')
            ->greaterThanOrEqual('display_order', 0, 'Display order cannot be negative');

        return $validator;
    }
}
