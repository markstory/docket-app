<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Subtask;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * Subtasks Model
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\BelongsTo $Tasks
 * @method \App\Model\Entity\Subtask newEmptyEntity()
 * @method \App\Model\Entity\Subtask newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Subtask[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Subtask get($primaryKey, $options = [])
 * @method \App\Model\Entity\Subtask findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Subtask patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Subtask[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Subtask|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Subtask saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Subtask>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Subtask> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Subtask>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Subtask> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SubtasksTable extends Table
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

        $this->setTable('subtasks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Tasks', [
            'foreignKey' => 'task_id',
            'joinType' => 'INNER',
        ]);

        $this->addBehavior('Timestamp');
        $this->addBehavior('CounterCache', [
            'Tasks' => [
                'subtask_count' => ['finder' => 'all'],
                'complete_subtask_count' => ['finder' => 'complete'],
            ],
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
            ->allowEmptyString('title');

        $validator
            ->scalar('body')
            ->allowEmptyString('body');

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
        $rules->add($rules->existsIn(['task_id'], 'Tasks'), ['errorField' => 'task_id']);

        return $rules;
    }

    public function beforeSave(EventInterface $event, Subtask $subtask)
    {
        if (!$subtask->ranking) {
            $subtask->ranking = $this->getNextRanking($subtask->task_id);
        }
    }

    public function getNextRanking(int $todoId)
    {
        $query = $this->find();
        $result = $query
            ->select([
                'max' => $query->func()->max('Subtasks.ranking'),
            ])
            ->where([
                'Subtasks.task_id' => $todoId,
                'Subtasks.completed' => false,
            ])
            ->firstOrFail();
        assert($result instanceof EntityInterface);

        return $result->max + 1;
    }

    public function findComplete(Query $query): Query
    {
        return $query->where(['Subtasks.completed' => true]);
    }

    public function findIncomplete(Query $query): Query
    {
        return $query->where(['Subtasks.completed' => false]);
    }

    public function move(Subtask $subtask, array $operation)
    {
        if (!isset($operation['ranking'])) {
            throw new InvalidArgumentException('A ranking is required');
        }
        $helper = new SimpleSortable($this, [
            'orderBy' => ['ranking', 'title'],
        ]);
        $conditions = [
            'task_id' => $subtask->task_id,
        ];
        $helper->move($subtask, $operation['ranking'], $conditions);
    }
}
