<?php
declare(strict_types=1);

namespace Calendar\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CalendarSubscriptions Model
 *
 * @property \Calendar\Model\Table\CalendarSourcesTable&\Cake\ORM\Association\BelongsTo $CalendarSources
 * @method \Calendar\Model\Entity\CalendarSubscription newEmptyEntity()
 * @method \Calendar\Model\Entity\CalendarSubscription newEntity(array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription[] newEntities(array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription get($primaryKey, $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Calendar\Model\Entity\CalendarSubscription saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSubscription>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSubscription> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSubscription>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Calendar\Model\Entity\CalendarSubscription> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CalendarSubscriptionsTable extends Table
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

        $this->setTable('calendar_subscriptions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('CalendarSources', [
            'className' => 'Calendar.CalendarSources',
            'foreignKey' => 'calendar_source_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('identifier')
            ->maxLength('identifier', 255)
            ->requirePresence('identifier', 'create')
            ->notEmptyString('identifier')
            ->add('identifier', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('verifier')
            ->maxLength('verifier', 255)
            ->requirePresence('verifier', 'create')
            ->notEmptyString('verifier');

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
        $rules->add($rules->isUnique(['identifier']), ['errorField' => 'identifier']);
        $rules->add(
            $rules->existsIn(['calendar_source_id'], 'CalendarSources'),
            ['errorField' => 'calendar_source_id']
        );

        return $rules;
    }

    public function findExpiring(Query $query, array $options): Query
    {
        $tomorrow = new DateTime('tomorrow');

        return $query->where([
            'CalendarSubscriptions.expires_at <=' => $tomorrow,
        ]);
    }
}
