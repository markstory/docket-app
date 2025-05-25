<?php
declare(strict_types=1);

namespace Feeds\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Feeds\Model\Entity\FeedCategory;

/**
 * FeedCategories Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Feeds\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\HasMany $FeedSubscriptions
 * @method \Feeds\Model\Entity\FeedCategory newEmptyEntity()
 * @method \Feeds\Model\Entity\FeedCategory newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedCategory> newEntities(array $data, array $options = [])
 * @method \Feeds\Model\Entity\FeedCategory get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Feeds\Model\Entity\FeedCategory findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Feeds\Model\Entity\FeedCategory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedCategory> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Feeds\Model\Entity\FeedCategory|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Feeds\Model\Entity\FeedCategory saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\Feeds\Model\Entity\FeedCategory>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\Feeds\Model\Entity\FeedCategory> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\Feeds\Model\Entity\FeedCategory>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\Feeds\Model\Entity\FeedCategory> deleteManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\ORM\SelectQuery findByUserId(int $userId)
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
            'className' => 'Feeds.FeedSubscriptions',
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

    public function updateUnreadItemCount(FeedCategory $category): void
    {
        $query = $this->FeedSubscriptions->find();
        $result = $query->select(['total' => $query->func()->sum('FeedSubscriptions.unread_item_count')])
            ->where(['FeedSubscriptions.feed_category_id' => $category->id])
            ->groupBy(['feed_category_id'])
            ->first();

        // result can be null when there are no subscriptions.
        $total = 0;
        if ($result !== null) {
            $total = $result->total;
        }
        $category->unread_item_count = $total;

        $this->saveOrFail($category);
    }
}
