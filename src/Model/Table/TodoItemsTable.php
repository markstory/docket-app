<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\TodoItem;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Validation\Validation;
use InvalidArgumentException;
use RuntimeException;

/**
 * TodoItems Model
 *
 * @property \App\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \App\Model\Table\TodoCommentsTable&\Cake\ORM\Association\HasMany $TodoComments
 * @property \App\Model\Table\TodoSubtasksTable&\Cake\ORM\Association\HasMany $TodoSubtasks
 * @property \App\Model\Table\TodoLabelsTable&\Cake\ORM\Association\BelongsToMany $TodoLabels
 *
 * @method \App\Model\Entity\TodoItem newEmptyEntity()
 * @method \App\Model\Entity\TodoItem newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TodoItem[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TodoItem get($primaryKey, $options = [])
 * @method \App\Model\Entity\TodoItem findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TodoItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TodoItem[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TodoItem|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TodoItem saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TodoItem[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TodoItem[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TodoItem[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TodoItem[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TodoItemsTable extends Table
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

        $this->setTable('todo_items');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('TodoComments', [
            'foreignKey' => 'todo_item_id',
        ]);
        $this->hasMany('TodoSubtasks', [
            'foreignKey' => 'todo_item_id',
            'propertyName' => 'subtasks',
            'sort' => ['TodoSubtasks.ranking' => 'ASC']
        ]);
        $this->belongsToMany('TodoLabels', [
            'propertyName' => 'labels',
            'foreignKey' => 'todo_item_id',
            'targetForeignKey' => 'todo_label_id',
            'joinTable' => 'todo_items_todo_labels',
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
            ->orderAsc('TodoItems.child_order');
    }

    public function findIncomplete(Query $query): Query
    {
        return $query->where(['TodoItems.completed' => false]);
    }

    public function findDueToday(Query $query): Query
    {
        return $query->where([
                'TodoItems.due_on IS NOT' => null,
                'TodoItems.due_on <=' => new FrozenDate('today')
            ])
            ->orderAsc('TodoItems.day_order');
    }

    public function findUpcoming(Query $query, array $options): Query
    {
        if (empty($options['start'])) {
            throw new RuntimeException('Missing required `start` option.');
        }
        $end = $options['start']->modify('+28 days');
        return $query->where([
                'TodoItems.due_on IS NOT' => null,
                'TodoItems.due_on >=' => $options['start'],
                'TodoItems.due_on <' => $end,
            ])
            ->orderAsc('TodoItems.due_on')
            ->orderAsc('TodoItems.day_order');
    }

    public function move(TodoItem $item, array $operation)
    {
        if (isset($operation['due_on'])) {
            if (!Validation::date($operation['due_on'], 'ymd')) {
                throw new InvalidArgumentException('due_on must be a valid date');
            }
            $item->due_on = $operation['due_on'];
        }
        $conditions = [
            'completed' => $item->completed,
            'project_id' => $item->project_id,
        ];
        if (isset($operation['day_order'])) {
            $property = 'day_order';
            $conditions['due_on IS'] = $item->due_on;
        } elseif (isset($operation['child_order'])) {
            $property = 'child_order';
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

        $query = $this->query()
            ->update()
            ->where($conditions);

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
