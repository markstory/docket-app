<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\FeedSubscription;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FeedSubscriptions Model
 *
 * @property \App\Model\Table\FeedsTable&\Cake\ORM\Association\BelongsTo $Feeds
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\FeedCategoriesTable&\Cake\ORM\Association\BelongsTo $FeedCategories
 * @property \App\Model\Table\SavedFeedItemsTable&\Cake\ORM\Association\HasMany $SavedFeedItems
 * @property \App\Model\Table\FeedItemsTable&\Cake\ORM\Association\HasMany $FeedItems
 * @method \App\Model\Entity\FeedSubscription newEmptyEntity()
 * @method \App\Model\Entity\FeedSubscription newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedSubscription> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FeedSubscription get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FeedSubscription findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\FeedSubscription patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedSubscription> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FeedSubscription|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\FeedSubscription saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscription>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscription> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscription>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscription> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FeedSubscriptionsTable extends Table
{
    /**
     * @var array
     */
    public const VIEW_CONTAIN = ['Feeds', 'FeedCategories'];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('feed_subscriptions');
        $this->setDisplayField('alias');
        $this->setPrimaryKey('id');

        $this->belongsTo('Feeds', [
            'foreignKey' => 'feed_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('FeedCategories', [
            'foreignKey' => 'feed_category_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('SavedFeedItems', [
            'foreignKey' => 'feed_subscription_id',
        ]);

        // Pass through relation to feeditems
        // Allows bypassing the Feeds relation for reads.
        $this->hasMany('FeedItems', [
            'className' => FeedItemsTable::class,
            'foreignKey' => 'feed_id',
            'bindingKey' => 'feed_id',
        ]);

        $this->addBehavior('Timestamp');
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
            ->integer('feed_id')
            ->notEmptyString('feed_id');

        $validator
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->integer('feed_category_id')
            ->notEmptyString('feed_category_id');

        $validator
            ->scalar('alias')
            ->maxLength('alias', 255)
            ->requirePresence('alias', 'create')
            ->notEmptyString('alias');

        $validator
            ->integer('ranking')
            ->requirePresence('ranking', 'create')
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
        $rules->add($rules->existsIn(['feed_id'], 'Feeds'), ['errorField' => 'feed_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['feed_category_id'], 'FeedCategories'), ['errorField' => 'feed_category_id']);

        return $rules;
    }

    public function findForFeed(SelectQuery $query, int $feedId): SelectQuery
    {
        return $query
            ->contain('FeedCategories')
            ->where(['FeedSubscriptions.feed_id' => $feedId]);
    }

    public function getNextRanking(int $feedCategoryId): int
    {
        $query = $this->find();
        $query
            ->select(['count' => $query->func()->count('*')])
            ->where(['FeedSubscriptions.feed_category_id' => $feedCategoryId]);

        return (int)$query->firstOrFail()->count;
    }

    /**
     * Update the unread_item_count
     */
    public function updateUnreadItemCount(FeedSubscription $subscription): void
    {
        $itemCount = $this->FeedItems
            ->find()
            ->where(['FeedItems.feed_id' => $subscription->feed_id])
            ->count();
        $readCount = $this->FeedItems->FeedItemUsers
            ->find()
            ->innerJoinWith('FeedItems')
            ->where([
                'FeedItemUsers.user_id' => $subscription->user_id,
                'FeedItems.feed_id' => $subscription->feed_id,
                'FeedItemUsers.read_at IS NOT' => null,
            ])
            ->count();
        $subscription->unread_item_count = $itemCount - $readCount;

        $this->saveOrFail($subscription);
    }
}
