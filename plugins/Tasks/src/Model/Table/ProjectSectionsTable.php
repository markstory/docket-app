<?php
declare(strict_types=1);

namespace Tasks\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Tasks\Model\Entity\ProjectSection;

/**
 * ProjectSections Model
 *
 * @property \Tasks\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \Tasks\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \Tasks\Model\Entity\ProjectSection newEmptyEntity()
 * @method \Tasks\Model\Entity\ProjectSection newEntity(array $data, array $options = [])
 * @method \Tasks\Model\Entity\ProjectSection[] newEntities(array $data, array $options = [])
 * @method \Tasks\Model\Entity\ProjectSection get($primaryKey, $options = [])
 * @method \Tasks\Model\Entity\ProjectSection findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Tasks\Model\Entity\ProjectSection patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Tasks\Model\Entity\ProjectSection[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Tasks\Model\Entity\ProjectSection|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Tasks\Model\Entity\ProjectSection saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \Tasks\Model\Entity\ProjectSection>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Tasks\Model\Entity\ProjectSection> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \Tasks\Model\Entity\ProjectSection>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \Tasks\Model\Entity\ProjectSection> deleteManyOrFail(iterable $entities, $options = [])
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
            'className' => 'Tasks.Projects',
            'foreignKey' => 'project_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Tasks', [
            'className' => 'Tasks.Tasks',
            'foreignKey' => 'section_id',
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

    public function beforeDelete(EventInterface $event, ProjectSection $section): void
    {
        $this->Tasks->updateAll(['section_id' => null], ['section_id' => $section->id]);
    }

    public function getNextRanking(int $projectId): int
    {
        $query = $this->find();
        $query
            ->select(['count' => $query->func()->count('*')])
            ->where(['ProjectSections.project_id' => $projectId]);

        return (int)$query->firstOrFail()->count;
    }

    public function move(ProjectSection $projectSection, array $operation): void
    {
        if (!isset($operation['ranking'])) {
            throw new InvalidArgumentException('A ranking is required');
        }
        $conditions = [
            'archived' => $projectSection->archived,
            'project_id' => $projectSection->project_id,
        ];

        $sorter = new SimpleSortable($this, [
            'field' => 'ranking',
            'orderBy' => ['ranking', 'name'],
        ]);
        $sorter->move($projectSection, $operation['ranking'], $conditions);
    }
}
