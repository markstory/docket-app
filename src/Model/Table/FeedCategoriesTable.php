<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FeedCategories Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\HasMany $FeedSubscriptions
 *
 * @method \App\Model\Entity\FeedCategory newEmptyEntity()
 * @method \App\Model\Entity\FeedCategory newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedCategory> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FeedCategory get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FeedCategory findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\FeedCategory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedCategory> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FeedCategory|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\FeedCategory saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\FeedCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedCategory>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedCategory> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedCategory>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedCategory> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FeedCategoriesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('feed_categories');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('FeedSubscriptions', [
            'foreignKey' => 'feed_category_id',
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->integer('color')
            ->requirePresence('color', 'create')
            ->greaterThanOrEqual('color', 0)
            ->lessThanOrEqual('color', 17);

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

    /**
     * Update the ranking on a list of category ids to match the provided order.
     */
    public function reorder(array $ids): void
    {
        $this->getConnection()->transactional(function () use ($ids): void {
            foreach ($ids as $ranking => $id) {
                $this->updateQuery()
                    ->set(['ranking' => $ranking])
                    ->where(['id' => $id])
                    ->execute();
            }
        });
    }

    public function findMenu(SelectQuery $query, array $options = [])
    {
        return $query
            ->contain('FeedSubscriptions')
            ->contain('FeedSubscriptions.Feeds')
            ->orderByAsc('ranking');
    }
}
