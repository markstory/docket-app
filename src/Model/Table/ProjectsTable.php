<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Project;
use App\Model\Entity\User;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;

/**
 * Projects Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @property \App\Model\Table\LabelsTable&\Cake\ORM\Association\HasMany $Labels
 * @property \App\Model\Table\ProjectSectionsTable&\Cake\ORM\Association\HasMany $Sections
 * @method \App\Model\Entity\Project newEmptyEntity()
 * @method \App\Model\Entity\Project newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Project[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Project get($primaryKey, $options = [])
 * @method \App\Model\Entity\Project findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Project patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Project[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Project|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Project saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Project>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Project> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Project>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\Project> deleteManyOrFail(iterable $entities, $options = [])
 * @method \Cake\ORM\Query findBySlug(string $slug)
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsTable extends Table
{
    public const NUM_COLORS = 16;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Tasks', [
            'foreignKey' => 'project_id',
        ]);
        $this->hasMany('Labels', [
            'foreignKey' => 'project_id',
        ]);
        $this->hasMany('Sections', [
            'className' => 'ProjectSections',
            'foreignKey' => 'project_id',
            'sort' => [
                'Sections.ranking' => 'ASC',
                'Sections.name' => 'ASC',
            ],
        ]);

        $this->addBehavior('Timestamp');
        $this->addBehavior('Sluggable', [
            'label' => ['name'],
            'reserved' => ['archived', 'add'],
            'scopeFields' => ['user_id'],
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
            ->integer('color')
            ->requirePresence('color', 'create')
            ->greaterThanOrEqual('color', 0)
            ->lessThanOrEqual('color', 17);

        $validator
            ->boolean('favorite')
            ->notEmptyString('favorite');

        $validator
            ->boolean('archived')
            ->notEmptyString('archived');

        $validator
            ->integer('ranking')
            ->notEmptyString('ranking');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    public function findTop(Query $query): Query
    {
        return $query
            ->orderByAsc('Projects.ranking')
            ->limit(25);
    }

    public function findActive(Query $query): Query
    {
        return $query->where(['Projects.archived' => false]);
    }

    public function findArchived(Query $query): Query
    {
        return $query->where(['Projects.archived' => true]);
    }

    public function getNextRanking(int $userId): int
    {
        $query = $this->find();
        $query
            ->select(['count' => $query->func()->count('*')])
            ->where(['Projects.user_id' => $userId]);

        return (int)$query->firstOrFail()->count;
    }

    public function createDefaultProject(User $user)
    {
        $project = $this->newEntity(['name' => 'Tasks', 'color' => 10]);
        $project->user_id = $user->id;
        $this->saveOrFail($project);
    }

    public function move(Project $project, array $operation)
    {
        if (!isset($operation['ranking'])) {
            throw new InvalidArgumentException('A ranking is required');
        }
        $conditions = [
            'archived' => $project->archived,
            'user_id' => $project->user_id,
        ];
        $sorter = new SimpleSortable($this, [
            'field' => 'ranking',
            'orderBy' => ['ranking'],
        ]);
        $sorter->move($project, $operation['ranking'], $conditions);
    }

    /**
     * Update the ranking on a list of projectids to match the provided order.
     */
    public function reorder(array $ids): void
    {
        $this->getConnection()->transactional(function () use ($ids) {
            foreach ($ids as $ranking => $id) {
                $this->updateQuery()
                    ->set(['ranking' => $ranking])
                    ->where(['id' => $id])
                    ->execute();
            }
        });
    }
}
