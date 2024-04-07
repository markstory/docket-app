<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Task;
use App\Model\Entity\User;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validation;
use Cake\Validation\Validator;
use InvalidArgumentException;

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
 * @method iterable<mixed, \App\Model\Entity\Task>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Task> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Task>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Task> deleteManyOrFail(iterable $entities, $options = [])
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
            'saveStrategy' => HasMany::SAVE_REPLACE,
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
            ->minLength('title', 1);

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

    /**
     * @inheritDoc
     */
    public function find(string $type = 'all', mixed ...$args): SelectQuery
    {
        // Add the default deleted condition unless the `deleted` option is set.
        $operator = empty($args['deleted']) ? 'IS' : 'IS NOT';
        $query = $this->selectQuery();
        $query->where(["Tasks.deleted_at {$operator}" => null]);

        return $this->callFinder($type, $query, ...$args);
    }

    /**
     * Finder to fetch tasks in section order.
     */
    public function findForProjectDetails(SelectQuery $query, $slug): SelectQuery
    {
        $query = $query
            ->leftJoinWith('ProjectSections')
            ->where(['Projects.slug' => $slug])
            ->orderByAsc('ProjectSections.ranking')
            ->orderByAsc('ProjectSections.name')
            ->orderByAsc('Tasks.child_order')
            ->orderByAsc('Tasks.title');

        return $query;
    }

    public function findComplete(SelectQuery $query): SelectQuery
    {
        return $query->where(['Tasks.completed' => true]);
    }

    public function findIncomplete(SelectQuery $query): SelectQuery
    {
        return $query->where(['Tasks.completed' => false]);
    }

    public function findForDate(SelectQuery $query, $date, $overdue = false): SelectQuery
    {
        $query = $query->where(['Tasks.due_on IS NOT' => null]);
        if ($overdue) {
            $query = $query->where(['Tasks.due_on <=' => $date]);
        } else {
            $query = $query->where(['Tasks.due_on =' => $date]);
        }

        return $query
            ->orderByAsc('Tasks.due_on')
            ->orderByAsc('Tasks.evening')
            ->orderByAsc('Tasks.day_order')
            ->orderByAsc('Tasks.title');
    }

    public function findUpcoming(SelectQuery $query, $start, $end): SelectQuery
    {
        return $query->where([
            'Tasks.due_on IS NOT' => null,
            'Tasks.due_on >=' => $start,
            'Tasks.due_on <' => $end,
        ])
            ->orderByAsc('Tasks.due_on')
            ->orderByAsc('Tasks.evening')
            ->orderByAsc('Tasks.day_order')
            ->orderByAsc('Tasks.title');
    }

    public function findOverdue(SelectQuery $query): SelectQuery
    {
        $today = new Date('today');

        return $query->where([
            'Tasks.due_on IS NOT' => null,
            'Tasks.due_on <' => $today,
        ])
            ->orderByAsc('Tasks.due_on')
            ->orderByAsc('Tasks.evening')
            ->orderByAsc('Tasks.day_order');
    }

    /**
     * Update an item so that it is appended to
     * both the day and project.
     */
    public function setNextOrderProperties(User $user, Task $item): void
    {
        $query = $this->find();
        $result = $query
            ->select([
                'max_child' => $query->func()->max('Tasks.child_order'),
            ])
            ->where([
                'Tasks.project_id' => $item->project_id,
            ])->firstOrFail();
        assert($result instanceof EntityInterface);
        $item->child_order = $result->max_child + 1;
        if (!$item->due_on) {
            return;
        }

        $query = $this->find();
        $result = $query
            ->select([
                'max_day' => $query->func()->max('Tasks.day_order'),
            ])
            ->innerJoinWith('Projects')
            ->where([
                'Projects.user_id' => $user->id,
                'Tasks.due_on' => $item->due_on,
            ])->firstOrFail();
        assert($result instanceof Task);
        $item->day_order = $result->max_day + 1;
    }

    public function move(Task $item, array $operation): void
    {
        if (!isset($item->project)) {
            throw new InvalidArgumentException('Task cannot be moved, it has no project data loaded.');
        }
        $this->validateMoveOperation($operation);

        // Constrain to projects owned by the same user.
        $projectQuery = $this->Projects->find()
            ->select(['Projects.id'])
            ->where(['Projects.user_id' => $item->project->user_id]);

        $conditions = [
            'completed' => $item->completed,
        ];
        $updateFields = [];
        if (isset($operation['due_on'])) {
            $updateFields['due_on'] = $operation['due_on'];
        }
        if (array_key_exists('section_id', $operation)) {
            $updateFields['section_id'] = $operation['section_id'];
        }
        if (isset($operation['evening'])) {
            $updateFields['evening'] = (bool)$operation['evening'];
        }

        if (isset($operation['day_order']) && !isset($operation['evening'])) {
            $property = 'day_order';
            $conditions['due_on IS'] = $updateFields['due_on'] ?? $item->due_on;
            $conditions['evening'] = false;
            $conditions['project_id IN'] = $projectQuery;
        } elseif (isset($operation['day_order']) && isset($operation['evening'])) {
            $property = 'day_order';
            $conditions['evening'] = $updateFields['evening'] ?? false;
            $conditions['due_on IS'] = $updateFields['due_on'] ?? $item->due_on;
            $conditions['project_id IN'] = $projectQuery;
        } elseif (array_key_exists('section_id', $operation) && isset($operation['child_order'])) {
            $property = 'child_order';
            $conditions['project_id'] = $item->project_id;
            $conditions['section_id IS'] = $updateFields['section_id'] ?? null;
        } elseif (isset($operation['child_order'])) {
            $property = 'child_order';
            $conditions['project_id'] = $item->project_id;
            $conditions['section_id IS'] = $item->section_id;
        } else {
            throw new InvalidArgumentException('Invalid request. Provide either day_order or child_order');
        }
        $value = $operation[$property] ?? -1;
        if ($value < 0) {
            throw new InvalidArgumentException('Invalid request. Order values must be 0 or greater.');
        }

        // We have to assume that all lists are not continuous ranges, and that the order
        // fields have holes in them. The holes can be introduced when items are
        // deleted/completed. Try to find the item at the target offset
        $currentItem = $this->find()
            ->where($conditions)
            ->orderByAsc($property)
            ->orderByAsc('title')
            ->offset((int)$value)
            ->first();

        $appendToBottom = false;
        // If we found a record at the current offset
        // use its order property for our update
        if ($currentItem instanceof EntityInterface) {
            $targetOffset = $currentItem->get($property);
        } else {
            $appendToBottom = true;
            $query = $this->find();
            $result = $query
                ->select(['max' => $query->func()->max($property)])
                ->where($conditions)
                ->firstOrFail();
            assert($result instanceof EntityInterface);
            $targetOffset = $result->max + 1;
        }

        $query = $this->updateQuery()->where($conditions);
        $current = $item->get($property);

        $item->set($property, $targetOffset);
        foreach ($updateFields as $key => $value) {
            if ($item->get($key) !== $value) {
                $item->set($key, $value);
            }
        }
        $difference = $current - $item->get($property);

        if ($appendToBottom === true) {
            // No records to update.
        } elseif (
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
        $this->getConnection()->transactional(function () use ($item, $query): void {
            if ($query->clause('set')) {
                $query->execute();
            }
            $this->saveOrFail($item);
        });
    }

    protected function validateMoveOperation(array $operation): void
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

    public function beforeSave(EventInterface $event, Task $task, ArrayObject $options): void
    {
        if ($task->isDirty('subtasks') && is_array($task->subtasks) && count($task->subtasks)) {
            // Force a dirty field so that counter cache always runs.
            // This isn't ideal but it works for now
            $task->subtasks[0]->setDirty('title');
        }
        if ($task->id && $task->isDirty('subtasks') && empty($task->subtasks)) {
            $this->Subtasks->deleteAll(['task_id' => $task->id]);
            $task->subtask_count = 0;
            $task->complete_subtask_count = 0;
        }
    }
}
