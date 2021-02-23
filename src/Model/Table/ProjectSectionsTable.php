<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ProjectSection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * ProjectSections Model
 *
 * @property \App\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \App\Model\Entity\ProjectSection newEmptyEntity()
 * @method \App\Model\Entity\ProjectSection newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ProjectSection[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ProjectSection get($primaryKey, $options = [])
 * @method \App\Model\Entity\ProjectSection findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ProjectSection patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ProjectSection[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ProjectSection|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ProjectSection saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ProjectSection[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ProjectSection[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ProjectSection[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ProjectSection[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectSectionsTable extends Table
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

        $this->setTable('project_sections');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'project_section_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('ranking')
            ->notEmptyString('ranking');

        $validator
            ->boolean('archived')
            ->notEmptyString('archived');

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

    public function getNextRanking(int $projectId): int
    {
        $query = $this->find();
        $query
            ->select(['count' => $query->func()->count('*')])
            ->where(['ProjectSections.project_id' => $projectId]);

        return (int)$query->firstOrFail()->count;
    }

    public function move(ProjectSection $projectSection, array $operation)
    {
        if (!isset($operation['ranking'])) {
            throw new InvalidArgumentException('A ranking is required');
        }
        $conditions = [
            'archived' => $projectSection->archived,
            'project_id' => $projectSection->project_id,
        ];

        // We have to assume that all lists are not continuous ranges, and that the order
        // fields have holes in them. The holes can be introduced when items are
        // deleted/completed. Try to find the item at the target offset
        $currentProjectSection = $this->find()
            ->where($conditions)
            ->orderAsc('ranking')
            ->offset($operation['ranking'])
            ->first();

        // If we found a record at the current offset
        // use its order property for our update
        $targetOffset = $operation['ranking'];
        if ($currentProjectSection instanceof EntityInterface) {
            $targetOffset = $currentProjectSection->get('ranking');
        }

        $query = $this->query()
            ->update()
            ->where($conditions);

        $current = $projectSection->get('ranking');
        $projectSection->set('ranking', $targetOffset);
        $difference = $current - $projectSection->get('ranking');

        if ($difference > 0) {
            // Move other items down, as the current item is going up
            // or is being moved from another group.
            $query
                ->set(['ranking' => $query->newExpr('ranking + 1')])
                ->where(function ($exp) use ($current, $targetOffset) {
                    return $exp->between('ranking', $targetOffset, $current);
                });
        } elseif ($difference < 0) {
            // Move other items up, as current item is going down
            $query
                ->set(['ranking' => $query->newExpr('ranking - 1')])
                ->where(function ($exp) use ($current, $targetOffset) {
                    return $exp->between('ranking', $current, $targetOffset);
                });
        }
        $this->getConnection()->transactional(function () use ($projectSection, $query) {
            if ($query->clause('set')) {
                $query->execute();
            }
            $this->saveOrFail($projectSection);
        });
    }
}
