<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use RuntimeException;

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
 * @method \App\Model\Entity\CalendarItem[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\CalendarItem[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\CalendarItem[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\CalendarItem[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
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
    public function findUpcoming(Query $query, array $options): Query
    {
        if (empty($options['start'])) {
            throw new RuntimeException('Missing required `start` option.');
        }

        $start = $options['start'];
        if (empty($options['end'])) {
            $options['end'] = $options['start']->modify('+28 days');
        }
        $end = $options['end'];

        return $query->where(function ($exp) use ($start, $end) {
            $tz = Configure::read('App.defaultTimezone');

            // Date values don't need times or timezones
            $date = $exp->and([
                'CalendarItems.start_date >=' => $start,
                'CalendarItems.end_date <=' => $end,
            ]);

            // Create datetimes and set timezones to UTC to match storage.
            $startTime = (new FrozenTime($start))->setTime(0, 0, 0)->setTimezone($tz);
            $endTime = (new FrozenTime($end))->setTime(23, 59, 59)->setTimezone($tz);
            $dateTime = $exp->and([
                'CalendarItems.start_time >=' => $startTime,
                'CalendarItems.end_time <' => $endTime,
            ]);

            return $exp->or([$date, $dateTime]);
        })
            ->contain('CalendarSources')
            ->orderDesc('CalendarItems.all_day')
            ->orderAsc('CalendarItems.start_date')
            ->orderAsc('CalendarItems.start_time')
            ->orderAsc('CalendarItems.title');
    }
}
