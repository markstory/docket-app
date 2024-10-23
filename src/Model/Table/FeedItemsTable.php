<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FeedItems Model
 *
 * @property \App\Model\Table\FeedsTable&\Cake\ORM\Association\BelongsTo $Feeds
 * @property \App\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\BelongsToMany $FeedSubscriptions
 *
 * @method \App\Model\Entity\FeedItem newEmptyEntity()
 * @method \App\Model\Entity\FeedItem newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedItem> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FeedItem get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FeedItem findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\FeedItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedItem> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FeedItem|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\FeedItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedItem>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedItem> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedItem>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedItem> deleteManyOrFail(iterable $entities, array $options = [])
 *
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
        $this->belongsToMany('FeedSubscriptions', [
            'foreignKey' => 'feed_item_id',
            'targetForeignKey' => 'feed_subscription_id',
            'joinTable' => 'feed_subscriptions_feed_items',
            'through' => 'FeedSubscriptionItem'
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

    public function findForFeed(SelectQuery $query, string $feedId, string|int $userId, string $id): SelectQuery
    {
        return $query->where([
            'FeedItems.feed_id' => $feedId,
            'FeedItems.id' => $id,
        ])->formatResults(function ($results) use ($userId, $feedId) {
            $sub = $this->FeedSubscriptions->find()
                ->where([
                    'FeedSubscriptions.user_id' => $userId,
                    'FeedSubscriptions.feed_id' => $feedId
                ])->first();

            return $results->map(function ($item) use ($sub) {
                if ($sub) {
                    $item->feed_subscriptions = [$sub];
                }
                return $item;
            });
        });
    }
}
