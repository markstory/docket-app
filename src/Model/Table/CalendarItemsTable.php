<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CalendarItems Model
 *
 * @property \App\Model\Table\CalendarSourcesTable&\Cake\ORM\Association\BelongsTo $CalendarSources
 * @property \App\Model\Table\ProvidersTable&\Cake\ORM\Association\BelongsTo $Providers
 * @method \App\Model\Entity\CalendarItem newEmptyEntity()
 * @method \App\Model\Entity\CalendarItem newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CalendarItem[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CalendarItem get($primaryKey, $options = [])
 * @method \App\Model\Entity\CalendarItem findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\CalendarItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CalendarItem[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CalendarItem|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CalendarItem saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \App\Model\Entity\CalendarItem>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\CalendarItem> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\CalendarItem>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\CalendarItem> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CalendarItemsTable extends Table
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

        $this->setTable('calendar_items');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('CalendarSources', [
            'foreignKey' => 'calendar_source_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Providers', [
            'foreignKey' => 'provider_id',
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->dateTime('start_time', ['iso8601'])
            ->allowEmptyDateTime('start_time');

        $validator
            ->date('start_date')
            ->allowEmptyDate('start_date');

        $validator
            ->dateTime('end_time', ['iso8601'])
            ->allowEmptyDateTime('end_time');

        $validator
            ->date('end_date')
            ->allowEmptyDate('end_date');

        $validator
            ->scalar('html_link')
            ->allowEmptyString('html_link');

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
            $rules->existsIn(['calendar_source_id'], 'CalendarSources'),
            ['errorField' => 'calendar_source_id']
        );

        return $rules;
    }

    /**
     * The `start` and `end` options are expected to be in
     * user timezone.
     */
    public function findUpcoming(Query $query, $start, $end, $timezone): Query
    {
        $userTimezone = $timezone;

        return $query->where(function ($exp) use ($start, $end, $userTimezone) {
            $serverTz = Configure::read('App.defaultTimezone');

            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');

            // Date values don't need times or timezones
            // All day calendar events from google last until the
            // next day so we add a date to the end date.
            $date = $exp->and([
                'CalendarItems.start_date >=' => $startDate,
                'CalendarItems.end_date <=' => $end->modify('+1 day')->format('Y-m-d'),
            ]);

            // Create datetimes and set timezones to UTC to match storage.
            $startTime = (new DateTime($startDate, $userTimezone))
                ->setTime(0, 0, 0)
                ->setTimezone($serverTz);
            $endTime = (new DateTime($endDate, $userTimezone))
                ->setTime(23, 59, 59)
                ->setTimezone($serverTz);
            $dateTime = $exp->and([
                'CalendarItems.start_time >=' => $startTime,
                'CalendarItems.end_time <' => $endTime,
            ]);

            return $exp->or([$date, $dateTime]);
        })
            ->contain('CalendarSources')
            ->orderByDesc('CalendarItems.all_day')
            ->orderByAsc('CalendarItems.start_date')
            ->orderByAsc('CalendarItems.start_time')
            ->orderByAsc('CalendarItems.title');
    }
}
