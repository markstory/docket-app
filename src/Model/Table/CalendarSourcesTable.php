<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CalendarSources Model
 *
 * @property \App\Model\Table\CalendarProvidersTable&\Cake\ORM\Association\BelongsTo $CalendarProviders
 * @property \App\Model\Table\ProvidersTable&\Cake\ORM\Association\BelongsTo $Providers
 * @property \App\Model\Table\CalendarItemsTable&\Cake\ORM\Association\HasMany $CalendarItems
 *
 * @method \App\Model\Entity\CalendarSource newEmptyEntity()
 * @method \App\Model\Entity\CalendarSource newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CalendarSource[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CalendarSource get($primaryKey, $options = [])
 * @method \App\Model\Entity\CalendarSource findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\CalendarSource patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CalendarSource[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CalendarSource|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CalendarSource saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CalendarSource[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\CalendarSource[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\CalendarSource[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\CalendarSource[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
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
            'foreignKey' => 'calendar_provider_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Providers', [
            'foreignKey' => 'provider_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('CalendarItems', [
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
            ->scalar('color')
            ->maxLength('color', 6)
            ->requirePresence('color', 'create')
            ->notEmptyString('color');

        $validator
            ->dateTime('last_sync')
            ->requirePresence('last_sync', 'create')
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
        $rules->add($rules->existsIn(['calendar_provider_id'], 'CalendarProviders'), ['errorField' => 'calendar_provider_id']);

        return $rules;
    }
}
