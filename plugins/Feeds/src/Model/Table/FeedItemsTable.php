<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\FeedCategory;
use App\Model\Entity\FeedItem;
use App\Model\Entity\FeedItemUser;
use App\Model\Entity\FeedSubscription;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FeedItems Model
 *
 * @property \App\Model\Table\FeedsTable&\Cake\ORM\Association\BelongsTo $Feeds
 * @property \App\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\BelongsToMany $FeedSubscriptions
 * @property \App\Model\Table\FeedItemUsersTable&\Cake\ORM\Association\HasOne $FeedItemUsers
 * @method \App\Model\Entity\FeedItem newEmptyEntity()
 * @method \App\Model\Entity\FeedItem newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedItem> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FeedItem get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FeedItem findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\FeedItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedItem> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FeedItem|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\FeedItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem>false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem> deleteManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\ORM\Query\SelectQuery<\App\Model\Entity\FeedItem> findById(int|string $id)
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FeedItemsTable extends Table
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

        $this->setTable('feed_items');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Feeds', [
            'foreignKey' => 'feed_id',
            'joinType' => 'INNER',
        ]);

        // This isn't a useful relation to contain()
        // but is useful for applying read conditions to
        // scope read queries.
        $this->belongsTo('FeedSubscriptions', [
            'foreignKey' => 'feed_id',
            'bindingKey' => 'feed_id',
        ]);

        // Useful in contain, but requires a userId condition
        // to be set to actually be useful.
        $this->hasOne('FeedItemUsers', [
            'foreignKey' => 'feed_item_id',
            'joinType' => 'LEFT',
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
            ->integer('feed_id')
            ->notEmptyString('feed_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('summary')
            ->requirePresence('summary', 'create')
            ->notEmptyString('summary');

        $validator
            ->dateTime('published_at')
            ->requirePresence('published_at', 'create')
            ->notEmptyDateTime('published_at');

        $validator
            ->scalar('thumbnail_image_url')
            ->maxLength('thumbnail_image_url', 255)
            ->allowEmptyString('thumbnail_image_url');

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

        return $rules;
    }

    public function findSubscribed(SelectQuery $query, int $userId, array $feedSubscriptionIds): SelectQuery
    {
        if (count($feedSubscriptionIds) == 0) {
            return $query->where('1 = 0');
        }

        return $query
            ->contain(['FeedItemUsers', 'FeedSubscriptions.FeedCategories'])
            ->matching('FeedSubscriptions')
            ->where([
                'OR' => [
                    'FeedItemUsers.user_id' => $userId,
                    'FeedItemUsers.user_id IS' => null,
                ],
                'FeedSubscriptions.id IN' => $feedSubscriptionIds,
            ])
            ->orderByDesc('FeedItems.published_at');
    }

    /**
     * Find all items in a subscription
     */
    public function findForSubscription(SelectQuery $query, FeedSubscription $subscription): SelectQuery
    {
        return $query
            ->contain(['FeedItemUsers', 'FeedSubscriptions'])
            ->where([
                'OR' => [
                    'FeedItemUsers.user_id' => $subscription->user_id,
                    'FeedItemUsers.user_id IS' => null,
                ],
                'FeedSubscriptions.id' => $subscription->id,
            ])
            ->orderByDesc('FeedItems.published_at');
    }

    public function findForCategory(SelectQuery $query, FeedCategory $category): SelectQuery
    {
        return $query
            ->innerJoinWith('FeedSubscriptions.FeedCategories')
            ->contain(['FeedSubscriptions', 'FeedItemUsers'])
            ->where([
                'OR' => [
                    'FeedItemUsers.user_id' => $category->user_id,
                    'FeedItemUsers.user_id IS' => null,
                ],
                'FeedSubscriptions.user_id' => $category->user_id,
                'FeedCategories.id' => $category->id,
            ])
            ->orderByDesc('FeedItems.published_at');
    }

    public function findMarkReadBulk(SelectQuery $query, array $ids): SelectQuery
    {
        return $query
            ->select(['id', 'feed_id', 'FeedSubscriptions.id', 'FeedSubscriptions.feed_category_id'])
            ->contain(['FeedSubscriptions.FeedCategories'])
            ->where(['FeedItems.id IN' => $ids]);
    }

    public function markRead(int $userId, FeedItem $feedItem): void
    {
        $entity = $this->FeedItemUsers->findOrCreate(
            [
                'user_id' => $userId,
                'feed_item_id' => $feedItem->id,
            ],
            function (FeedItemUser $entity) {
                // set read_at during creation
                $entity->read_at = DateTime::now();

                return $entity;
            }
        );
        // If read_at somehow gets cleared set it back
        if ($entity->read_at === null) {
            $entity->read_at = DateTime::now();
            $this->FeedItemUsers->saveOrFail($entity);
        }
    }

    public function markManyRead(int $userId, array $ids): void
    {
        $items = $this->find()
            ->where(['FeedItems.id IN' => $ids])
            ->orderByDesc('FeedItems.published_at');

        foreach ($items as $item) {
            /** @var \App\Model\Entity\FeedItem $item */
            $this->markRead($userId, $item);
        }
    }
}
