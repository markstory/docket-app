<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Task;
use App\Model\Entity\User;
use Cake\Datasource\EntityInterface;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validation;
use Cake\Validation\Validator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Tasks Model
 *
 * @property \App\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \App\Model\Table\SubtasksTable&\Cake\ORM\Association\HasMany $Subtasks
 * @property \App\Model\Table\LabelsTable&\Cake\ORM\Association\BelongsToMany $Labels
 * @property \App\Model\Table\ProjectSectionsTable&\Cake\ORM\Association\BelongsTo $ProjectSections
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

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('ProjectSections', [
            'foreignKey' => 'section_id',
            'joinType' => 'LEFT',
        ]);
        $this->hasMany('Subtasks', [
            'foreignKey' => 'task_id',
            'propertyName' => 'subtasks',
            'sort' => [
                'Subtasks.ranking' => 'ASC',
                'Subtasks.title' => 'ASC',
            ],
        ]);
        $this->belongsToMany('Labels', [
            'propertyName' => 'labels',
            'foreignKey' => 'task_id',
            'targetForeignKey' => 'label_id',
            'joinTable' => 'labels_tasks',
        ]);

        $this->addBehavior('Timestamp');
        $this->addBehavior('CounterCache', [
            'Projects' => [
                'incomplete_task_count' => ['finder' => 'incomplete'],
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

        $validator
            ->date('due_on')
            ->allowEmptyDate('due_on');

        $validator
            ->boolean('completed')
            ->notEmptyString('completed');

        $validator
            ->boolean('evening')
            ->notEmptyString('evening');

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

        $rules->add(
            function (Task $task) {
                if (!$task->section_id) {
                    return true;
                }

                return $this->ProjectSections->exists([
                    'project_id' => $task->project_id,
                    'id' => $task->section_id,
                ]);
            },
            'section',
            [
                'errorField' => 'section_id',
                'message' => __('Section does not belong to the task project.'),
            ]
        );

        return $rules;
    }

    public function findForProject(Query $query, array $options): Query
    {
        if (empty($options['slug'])) {
            throw new RuntimeException('Missing required slug option');
        }

        return $query
            ->where(['Projects.slug' => $options['slug']])
            ->orderAsc('Tasks.child_order')
            ->orderAsc('Tasks.title');
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
                'Tasks.due_on <=' => new FrozenDate('today', $timezone),
            ])
            ->orderAsc('Tasks.evening')
            ->orderAsc('Tasks.day_order')
            ->orderAsc('Tasks.title');
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
            ->orderAsc('Tasks.evening')
            ->orderAsc('Tasks.day_order')
            ->orderAsc('Tasks.title');
    }

    public function findOverdue(Query $query): Query
    {
        $today = new FrozenDate('today');

        return $query->where([
                'Tasks.due_on IS NOT' => null,
                'Tasks.due_on >=' => $today,
            ])
            ->orderAsc('Tasks.due_on')
            ->orderAsc('Tasks.evening')
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
        $this->validateMoveOperation($operation);

        // Constrain to projects owned by the same user.
        $projectQuery = $this->Projects->find()
            ->select(['Projects.id'])
            ->where(['Projects.user_id' => $item->project->user_id]);

        $updateFields = [];
        $conditions = [
            'completed' => $item->completed,
        ];
        if (isset($operation['due_on'])) {
            $item->due_on = $operation['due_on'];
        }
        if (array_key_exists('section_id', $operation)) {
            $item->section_id = $operation['section_id'];
        }
        if (isset($operation['evening'])) {
            $item->evening = (bool)$operation['evening'];
        }

        if (isset($operation['day_order']) && !isset($operation['evening'])) {
            $property = 'day_order';
            $conditions['due_on IS'] = $item->due_on;
            $conditions['evening'] = false;
            $conditions['project_id IN'] = $projectQuery;
        } elseif (isset($operation['day_order']) && isset($operation['evening'])) {
            $property = 'day_order';

            $conditions['evening'] = $operation['evening'];
            $conditions['due_on IS'] = $item->due_on;
            $conditions['project_id IN'] = $projectQuery;
        } elseif (array_key_exists('section_id', $operation) && isset($operation['child_order'])) {
            $property = 'child_order';

            $conditions['section_id IS'] = $item->section_id;
        } elseif (isset($operation['child_order'])) {
            $property = 'child_order';

            $conditions['project_id'] = $item->project_id;
            $conditions['section_id IS'] = $item->section_id;
        } else {
            throw new InvalidArgumentException('Invalid request. Provide either day_order or child_order');
        }
        if ($operation[$property] < 0) {
            throw new InvalidArgumentException('Invalid request. Order values must be 0 or greater.');
        }

        // We have to assume that all lists are not continuous ranges, and that the order
        // fields have holes in them. The holes can be introduced when items are
        // deleted/completed. Try to find the item at the target offset
        $currentItem = $this->find()
            ->where($conditions)
            ->orderAsc($property)
            ->orderAsc('title')
            ->offset($operation[$property])
            ->first();

        // If we found a record at the current offset
        // use its order property for our update
        $targetOffset = $operation[$property];
        if ($currentItem instanceof EntityInterface) {
            $targetOffset = $currentItem->get($property);
        }

        $query = $this->query()
            ->update()
            ->innerJoinWith('Projects')
            ->where($conditions);

        $current = $item->get($property);

        $item->set($property, $targetOffset);
        $difference = $current - $item->get($property);

        if (
            $item->isDirty('due_on') ||
            $item->isDirty('evening') ||
            $item->isDirty('section_id')
        ) {
            // Moving an item to a new list. Shift the remainder of
            // the new list down.
            $query
                ->set([$property => $query->newExpr($property . ' + 1')])
                ->where(["{$property} >=" => $targetOffset]);
        } elseif ($difference >= 0) {
            // Move other items down, as the current item is going up
            // or is being moved from another group.
            $query
                ->set([$property => $query->newExpr($property . ' + 1')])
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

    protected function validateMoveOperation(array $operation)
    {
        if (isset($operation['due_on'])) {
            if (!Validation::date($operation['due_on'], 'ymd')) {
                throw new InvalidArgumentException('due_on must be a valid date');
            }
        }
        if (isset($operation['day_order']) && isset($operation['child_order'])) {
            throw new InvalidArgumentException('Cannot set day and child order at the same time');
        }
        if (isset($operation['child_order']) && isset($operation['due_on'])) {
            throw new InvalidArgumentException('Cannot set child order and due_on at the same time');
        }
        if (isset($operation['day_order']) && isset($operation['section_id'])) {
            throw new InvalidArgumentException('Cannot set day order and section at the same time');
        }
        if (
            isset($operation['section_id']) &&
            !($operation['section_id'] === '' || Validation::isInteger($operation['section_id']))
        ) {
            throw new InvalidArgumentException('section_id must be a number or ""');
        }
    }
}
