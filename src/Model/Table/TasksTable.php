<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Task;
use App\Model\Entity\User;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Validation\Validation;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tasks Model
 *
 * @property \App\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \App\Model\Table\TaskCommentsTable&\Cake\ORM\Association\HasMany $TaskComments
 * @property \App\Model\Table\SubtasksTable&\Cake\ORM\Association\HasMany $Subtasks
 * @property \App\Model\Table\LabelsTable&\Cake\ORM\Association\BelongsToMany $Labels
 *
 * @method \App\Model\Entity\Task newEmptyEntity()
 * @method \App\Model\Entity\Task newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Task[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Task get($primaryKey, $options = [])
 * @method \App\Model\Entity\Task findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Task patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Task[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Task|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Task saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TasksTable extends Table
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

        $this->setTable('tasks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('TaskComments', [
            'foreignKey' => 'task_id',
        ]);
        $this->hasMany('Subtasks', [
            'foreignKey' => 'task_id',
            'propertyName' => 'subtasks',
            'sort' => ['Subtasks.ranking' => 'ASC']
        ]);
        $this->belongsToMany('Labels', [
            'propertyName' => 'labels',
            'foreignKey' => 'task_id',
            'targetForeignKey' => 'label_id',
            'joinTable' => 'labels_tasks',
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

        $validator
            ->date('due_on')
            ->allowEmptyDate('due_on');

        $validator
            ->boolean('completed')
            ->notEmptyString('completed');

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
        $rules->add($rules->existsIn(['project_id'], 'Projects'), ['errorField' => 'project_id']);

        return $rules;
    }

    public function findForProject(Query $query, array $options): Query
    {
        if (empty($options['slug'])) {
            throw new RuntimeException('Missing required slug option');
        }
        return $query
            ->where(['Projects.slug' => $options['slug']])
            ->orderAsc('Tasks.child_order');
    }

    public function findComplete(Query $query): Query
    {
        return $query->where(['Tasks.completed' => true]);
    }

    public function findIncomplete(Query $query): Query
    {
        return $query->where(['Tasks.completed' => false]);
    }

    public function findDueToday(Query $query, array $options): Query
    {
        $timezone = $options['timezone'] ?: 'UTC';
        return $query->where([
                'Tasks.due_on IS NOT' => null,
                'Tasks.due_on <=' => new FrozenDate('today', $timezone)
            ])
            ->orderAsc('Tasks.day_order');
    }

    public function findUpcoming(Query $query, array $options): Query
    {
        if (empty($options['start'])) {
            throw new RuntimeException('Missing required `start` option.');
        }
        if (empty($options['end'])) {
            $options['end'] = $options['start']->modify('+28 days');
        }
        return $query->where([
                'Tasks.due_on IS NOT' => null,
                'Tasks.due_on >=' => $options['start'],
                'Tasks.due_on <' => $options['end'],
            ])
            ->orderAsc('Tasks.due_on')
            ->orderAsc('Tasks.day_order');
    }

    public function findOverdue(Query $query): Query
    {
        $today = new FrozenDate('today');
        return $query->where([
                'Tasks.due_on IS NOT' => null,
                'Tasks.due_on >=' => $today,
            ])
            ->orderAsc('Tasks.due_on')
            ->orderAsc('Tasks.day_order');
    }

    /**
     * Update an item so that it is appended to
     * both the day and project.
     */
    public function setNextOrderProperties(User $user, Task $item)
    {
        $query = $this->find();
        $result = $query->select([
            'max_child' => $query->func()->max('Tasks.child_order'),
        ])
        ->where([
            'Tasks.project_id' => $item->project_id,
        ])->firstOrFail();
        $item->child_order = $result->max_child + 1;
        if (!$item->due_on) {
            return;
        }

        $query = $this->find();
        $result = $query->select([
            'max_day' => $query->func()->max('Tasks.day_order'),
        ])
        ->innerJoinWith('Projects')
        ->where([
            'Projects.user_id' => $user->id,
            'Tasks.due_on' => $item->due_on,
        ])->firstOrFail();
        $item->day_order = $result->max_day + 1;
    }

    public function move(Task $item, array $operation)
    {
        if (!isset($item->project)) {
            throw new InvalidArgumentException('Task cannot be moved, it has no project data loaded.');
        }
        if (isset($operation['due_on'])) {
            if (!Validation::date($operation['due_on'], 'ymd')) {
                throw new InvalidArgumentException('due_on must be a valid date');
            }
            $item->due_on = $operation['due_on'];
        }
        $conditions = [
            'completed' => $item->completed,
        ];
        if (isset($operation['day_order'])) {
            $property = 'day_order';
            $conditions['due_on IS'] = $item->due_on;
        } elseif (isset($operation['child_order'])) {
            $property = 'child_order';
            $conditions['project_id'] = $item->project_id;
        } else {
            throw new InvalidArgumentException('Invalid request. Provide either day_order or child_order');
        }

        // We have to assume that all lists are not continuous ranges, and that the order
        // fields have holes in them. The holes can be introduced when items are 
        // deleted/completed. Try to find the item at the target offset
        $currentItem = $this->find()
            ->where($conditions)
            ->orderAsc($property)
            ->offset($operation[$property])
            ->first();

        // If we found a record at the current offset
        // use its order property for our update
        $targetOffset = $operation[$property];
        if ($currentItem) {
            $targetOffset = $currentItem->get($property);
        }

        // Constrain to projects owned by the same user.
        $projectQuery = $this->Projects->find()
            ->select(['Projects.id'])
            ->where(['Projects.user_id' => $item->project->user_id]);

        $query = $this->query()
            ->update()
            ->innerJoinWith('Projects')
            ->where($conditions)
            ->where(['project_id IN' => $projectQuery]);

        $current = $item->get($property);
        $item->set($property, $targetOffset);
        $difference = $current - $item->get($property);

        if ($item->isDirty('due_on')) {
            // Moving an item to a new list. Shift the remainder of
            // the new list down.
            $query
                ->set([$property => $query->newExpr($property . " + 1")])
                ->where(["{$property} >=" => $targetOffset]);
        } elseif ($difference > 0) {
            // Move other items down, as the current item is going up
            // or is being moved from another group.
            $query
                ->set([$property => $query->newExpr($property . " + 1")])
                ->where(function ($exp) use ($property, $current, $targetOffset) {
                    return $exp->between($property, $targetOffset, $current);
                });
        } elseif ($difference < 0) {
            // Move other items up, as current item is going down
            $query
                ->set([$property => $query->newExpr($property . ' - 1')])
                ->where(function ($exp) use ($property, $current, $targetOffset) {
                    return $exp->between($property, $current, $targetOffset);
                });
        }
        $this->getConnection()->transactional(function () use ($item, $query) {
            if ($query->clause('set')) {
                $query->execute();
            }
            $this->saveOrFail($item);
        });
    }
}
