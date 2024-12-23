<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SavedFeedItems Model
 *
 * @property \App\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\BelongsTo $FeedSubscriptions
 * @method \App\Model\Entity\SavedFeedItem newEmptyEntity()
 * @method \App\Model\Entity\SavedFeedItem newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\SavedFeedItem> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SavedFeedItem get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SavedFeedItem findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SavedFeedItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\SavedFeedItem> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SavedFeedItem|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SavedFeedItem saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\SavedFeedItem>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SavedFeedItem> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SavedFeedItem>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SavedFeedItem> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SavedFeedItemsTable extends Table
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

        $this->setTable('saved_feed_items');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('FeedSubscriptions', [
            'foreignKey' => 'feed_subscription_id',
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('body')
            ->requirePresence('body', 'create')
            ->notEmptyString('body');

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
        $rules->add(
            $rules->existsIn(['feed_subscription_id'], 'FeedSubscriptions'),
            ['errorField' => 'feed_subscription_id']
        );

        return $rules;
    }
}
