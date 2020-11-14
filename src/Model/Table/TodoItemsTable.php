<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
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
        ]);
        $this->belongsToMany('TodoLabels', [
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
            throw new RuntimeException('Missing required slug argument');
        }
        return $query->where(['Projects.slug' => $options['slug']]);
    }

    public function findIncomplete(Query $query): Query
    {
        return $query->where(['TodoItems.completed' => false]);
    }
}
