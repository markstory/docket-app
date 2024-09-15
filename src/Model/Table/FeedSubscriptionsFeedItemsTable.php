<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FeedSubscriptionsFeedItems Model
 *
 * @property \App\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\BelongsTo $FeedSubscriptions
 * @property \App\Model\Table\FeedItemsTable&\Cake\ORM\Association\BelongsTo $FeedItems
 *
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem newEmptyEntity()
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedSubscriptionsFeedItem> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedSubscriptionsFeedItem> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\FeedSubscriptionsFeedItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscriptionsFeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedSubscriptionsFeedItem>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscriptionsFeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedSubscriptionsFeedItem> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscriptionsFeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedSubscriptionsFeedItem>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedSubscriptionsFeedItem>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\FeedSubscriptionsFeedItem> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FeedSubscriptionsFeedItemsTable extends Table
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

        $this->setTable('feed_subscriptions_feed_items');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('FeedSubscriptions', [
            'foreignKey' => 'feed_subscription_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('FeedItems', [
            'foreignKey' => 'feed_item_id',
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
            ->integer('feed_subscription_id')
            ->notEmptyString('feed_subscription_id');

        $validator
            ->integer('feed_item_id')
            ->notEmptyString('feed_item_id');

        $validator
            ->boolean('is_read')
            ->notEmptyString('is_read');

        $validator
            ->boolean('is_saved')
            ->notEmptyString('is_saved');

        $validator
            ->boolean('is_hidden')
            ->notEmptyString('is_hidden');

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
        $rules->add($rules->existsIn(['feed_subscription_id'], 'FeedSubscriptions'), ['errorField' => 'feed_subscription_id']);
        $rules->add($rules->existsIn(['feed_item_id'], 'FeedItems'), ['errorField' => 'feed_item_id']);

        return $rules;
    }
}
