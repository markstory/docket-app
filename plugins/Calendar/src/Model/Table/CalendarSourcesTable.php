<?php
declare(strict_types=1);

namespace Calendar\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CalendarSources Model
 *
 * @property \Calendar\Model\Table\CalendarProvidersTable&\Cake\ORM\Association\BelongsTo $CalendarProviders
 * @property \Calendar\Model\Table\ProvidersTable&\Cake\ORM\Association\BelongsTo $Providers
 * @property \Calendar\Model\Table\CalendarItemsTable&\Cake\ORM\Association\HasMany $CalendarItems
 * @property \Calendar\Model\Table\CalendarItemsTable&\Cake\ORM\Association\HasMany $CalendarSubscriptions
 * @method \Calendar\Model\Entity\CalendarSource newEmptyEntity()
 * @method \Calendar\Model\Entity\CalendarSource newEntity(array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSource[] newEntities(array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSource get($primaryKey, $options = [])
 * @method \Calendar\Model\Entity\CalendarSource findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Calendar\Model\Entity\CalendarSource patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSource[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSource|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Calendar\Model\Entity\CalendarSource saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSource>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSource> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSource>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSource> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CalendarSourcesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('calendar_sources');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('CalendarProviders', [
            'className' => 'Calendar.CalendarProviders',
            'foreignKey' => 'calendar_provider_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('CalendarItems', [
            'className' => 'Calendar.CalendarItems',
            'foreignKey' => 'calendar_source_id',
        ]);
        $this->hasMany('CalendarSubscriptions', [
            'className' => 'Calendar.CalendarSubscriptions',
            'foreignKey' => 'calendar_source_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('color')
            ->requirePresence('color', 'create')
            ->greaterThanOrEqual('color', 0)
            ->lessThanOrEqual('color', 17);

        $validator
            ->dateTime('last_sync')
            ->notEmptyDateTime('last_sync');

        $validator
            ->scalar('sync_token')
            ->maxLength('sync_token', 255)
            ->allowEmptyString('sync_token');

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
        $rules->add(
            $rules->existsIn(['calendar_provider_id'], 'CalendarProviders'),
            ['errorField' => 'calendar_provider_id']
        );

        return $rules;
    }

    public function findMissingSubscription(Query $query, array $options): Query
    {
        return $query
            ->leftJoinWith('CalendarSubscriptions')
            ->where(['CalendarSubscriptions.id IS' => null]);
    }
}
