<?php
declare(strict_types=1);

namespace Calendar\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CalendarProviders Model
 *
 * @property \Calendar\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Calendar\Model\Table\CalendarSourcesTable&\Cake\ORM\Association\HasMany $CalendarSources
 * @method \Calendar\Model\Entity\CalendarProvider newEmptyEntity()
 * @method \Calendar\Model\Entity\CalendarProvider newEntity(array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider[] newEntities(array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider get($primaryKey, $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Calendar\Model\Entity\CalendarProvider saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarProvider>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarProvider> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarProvider>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarProvider> deleteManyOrFail(iterable $entities, $options = [])
 */
class CalendarProvidersTable extends Table
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

        $this->setTable('calendar_providers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('CalendarSources', [
            'className' => 'Calendar.CalendarSources',
            'foreignKey' => 'calendar_provider_id',
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
            ->scalar('kind')
            ->maxLength('kind', 255)
            ->requirePresence('kind', 'create')
            ->notEmptyString('kind');

        $validator
            ->scalar('identifier')
            ->maxLength('identifier', 255)
            ->requirePresence('identifier', 'create')
            ->notEmptyString('identifier');

        $validator
            ->scalar('display_name')
            ->maxLength('display_name', 255);

        $validator
            ->scalar('access_token')
            ->maxLength('access_token', 255)
            ->requirePresence('access_token', 'create')
            ->notEmptyString('access_token');

        $validator
            ->scalar('refresh_token')
            ->maxLength('refresh_token', 255)
            ->requirePresence('refresh_token', 'create')
            ->notEmptyString('refresh_token');

        $validator
            ->dateTime('token_expiry')
            ->requirePresence('token_expiry', 'create')
            ->notEmptyDateTime('token_expiry');

        $validator->boolean('synced');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
