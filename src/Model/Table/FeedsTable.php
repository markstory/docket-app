<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Feed;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Feeds Model
 *
 * @property \App\Model\Table\FeedItemsTable&\Cake\ORM\Association\HasMany $FeedItems
 * @property \App\Model\Table\FeedSubscriptionsTable&\Cake\ORM\Association\HasMany $FeedSubscriptions
 *
 * @method \App\Model\Entity\Feed newEmptyEntity()
 * @method \App\Model\Entity\Feed newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Feed> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Feed get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Feed findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Feed patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Feed> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Feed|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Feed saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Feed>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Feed>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Feed>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Feed> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Feed>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Feed>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Feed>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Feed> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FeedsTable extends Table
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

        $this->setTable('feeds');
        $this->setDisplayField('url');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('FeedItems', [
            'foreignKey' => 'feed_id',
        ]);
        $this->hasMany('FeedSubscriptions', [
            'foreignKey' => 'feed_id',
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
            ->scalar('url')
            ->maxLength('url', 255)
            ->requirePresence('url', 'create')
            ->notEmptyString('url');

        $validator
            ->integer('refresh_interval')
            ->requirePresence('refresh_interval', 'create')
            ->notEmptyString('refresh_interval');

        $validator
            ->dateTime('last_refresh')
            ->allowEmptyDateTime('last_refresh');

        return $validator;
    }

    public function findByUrlOrNew(string $url): Feed
    {
        $existing = $this->findByUrl($url)->first();
        if ($existing) {
            return $existing;
        }

        $feed = $this->newEntity([
            'url' => $url,
            // TODO figure out a better default for refreshing than 7 days.
            'refresh_interval' => 60 * 60 * 24 * 7,
        ]);

        return $feed;
    }

    public function findActiveSubscriptions(SelectQuery $query, array $options): SelectQuery
    {
        $query->innerJoinWith('FeedSubscriptions')
            ->where(['FeedSubscriptions.id IS NOT' => null]);

        return $query;
    }
}
