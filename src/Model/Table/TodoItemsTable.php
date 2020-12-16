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

    /**
     * Reorder a set of items in the scope.
     *
     * Only the provided items will be reordered, other items
     * will be left in their current order. The lowest order value will
     * be used as the root of the sort operation.
     *
     * @param string $scope
     * @param \App\Model\Entity\TodoItem[] $items
     * @return void
     */
    public function reorder(string $scope, array $items)
    {
        if (!in_array($scope, ['child', 'day'])) {
            throw new RuntimeException("Invalid scope {$scope} used");
        }
        if (empty($items)) {
            return;
        }
        if ($scope === 'child') {
            $this->reorderByChild($items);
        }
        if ($scope === 'day') {
            $this->reorderByDay($items);
        }
    }

    protected function reorderByChild(array $items)
    {
        $projectId = $items[0]->project_id;
        foreach ($items as $item) {
            if ($item->project_id !== $projectId) {
                throw new InvalidArgumentException('Cannot order by scope as there are multiple projects.');
            }
        }
        $minValue = 0;
        $orderMap = [];
        foreach ($items as $i => $item) {
            if ($item->child_order < $minValue) {
                $minValue = $item->child_order;
            }
            $orderMap[$item->id] = $i;
        }
        $ids = array_keys($orderMap);

        $query = $this->query();
        $cases = $values = [];
        foreach ($orderMap as $id => $value) {
            $cases[] = $query->newExpr()->eq('id', $id);
            $values[] = $minValue + $value;
        }
        $case = $query->newExpr()
            ->addCase($cases, $values);
        $query
            ->update()
            ->set(['child_order' => $case])
            ->where(['id IN' => $ids]);
        $statement = $query->execute();

        return $statement->rowCount();
    }

    protected function reorderByDay(array $items)
    {
        $minValue = 0;
        $orderMap = [];
        foreach ($items as $i => $item) {
            if ($item->day_order < $minValue) {
                $minValue = $item->day_order;
            }
            $orderMap[$item->id] = $i;
        }
        $ids = array_keys($orderMap);

        $query = $this->query();
        $cases = $values = [];
        foreach ($orderMap as $id => $value) {
            $cases[] = $query->newExpr()->eq('id', $id);
            $values[] = $minValue + $value;
        }
        $case = $query->newExpr()
            ->addCase($cases, $values);
        $query
            ->update()
            ->set(['day_order' => $case])
            ->where(['id IN' => $ids]);
        $statement = $query->execute();

        return $statement->rowCount();
    }

    public function move(TodoItem $item, array $operation)
    {
        if (isset($operation['due_on'])) {
            if (!Validation::date($operation['due_on'], 'ymd')) {
                throw new InvalidArgumentException('due_on must be a valid date');
            }
            $item->due_on = $operation['due_on'];
        }
        $query = $this->query()
            ->update()
            ->where([
                'project_id' => $item->project_id,
                'completed' => $item->completed,
            ]);

        if (isset($operation['day_order'])) {
            $property = 'day_order';
        } elseif (isset($operation['child_order'])) {
            $property = 'child_order';
        } else {
            throw new InvalidArgumentException('Invalid request. Provide either day_order or child_order');
        }

        $current = $item->get($property);
        if ($item->isDirty('due_on')) {
            // Arbitrary number should be good enough for most days.
            $current = 500;
        }
        $item->set($property, $operation[$property]);

        $difference = $current - $item->get($property);
        if ($difference === 0) {
            throw new InvalidArgumentException('Position unchanged.');
        }

        if ($difference > 0) {
            // Move other items down, as the current item is going up
            $query
                ->set([$property => $query->newExpr($property . " + 1")])
                ->where(function ($exp) use ($property, $current, $item) {
                    return $exp->between($property, $item->get($property), $current);
                });
        }
        if ($difference < 0){
            // Move other items up, as current item is going down
            $query
                ->set([$property => $query->newExpr($property . ' - 1')])
                ->where(function ($exp) use ($property, $current, $item) {
                    return $exp->between($property, $current, $item->get($property));
                });
        }
        $this->getConnection()->transactional(function () use ($item, $query) {
            $query->execute();
            $this->saveOrFail($item);
        });
    }
}
