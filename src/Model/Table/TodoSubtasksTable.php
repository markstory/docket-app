<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TodoSubtasks Model
 *
 * @property \App\Model\Table\TodoItemsTable&\Cake\ORM\Association\BelongsTo $TodoItems
 *
 * @method \App\Model\Entity\TodoSubtask newEmptyEntity()
 * @method \App\Model\Entity\TodoSubtask newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TodoSubtask[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TodoSubtask get($primaryKey, $options = [])
 * @method \App\Model\Entity\TodoSubtask findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\TodoSubtask patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TodoSubtask[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TodoSubtask|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TodoSubtask saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TodoSubtask[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TodoSubtask[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\TodoSubtask[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\TodoSubtask[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TodoSubtasksTable extends Table
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

        $this->setTable('todo_subtasks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('TodoItems', [
            'foreignKey' => 'todo_item_id',
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
        $rules->add($rules->existsIn(['todo_item_id'], 'TodoItems'), ['errorField' => 'todo_item_id']);

        return $rules;
    }

    /**
     * Update the order on the list of subtasks
     *
     * @param \App\Model\Entity\TodoSubtask[] $items
     * @return void
     */
    public function reorder(array $items)
    {
        $minValue = 0;
        $orderMap = [];
        foreach ($items as $i => $item) {
            if ($item->ranking < $minValue) {
                $minValue = $item->ranking;
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
            ->set(['ranking' => $case])
            ->where(['id IN' => $ids]);
        $statement = $query->execute();

        return $statement->rowCount();
    }
}
