<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FeedItemUsers Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\FeedItemsTable&\Cake\ORM\Association\BelongsTo $FeedItems
 * @method \App\Model\Entity\FeedItemUser newEmptyEntity()
 * @method \App\Model\Entity\FeedItemUser newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedItemUser> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FeedItemUser get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\FeedItemUser findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\FeedItemUser patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\FeedItemUser> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FeedItemUser|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\FeedItemUser saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItemUser>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItemUser> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItemUser>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\FeedItemUser> deleteManyOrFail(iterable $entities, array $options = [])
 */
class FeedItemUsersTable extends Table
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

        $this->setTable('feed_item_users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->integer('feed_item_id')
            ->notEmptyString('feed_item_id');

        $validator
            ->dateTime('read_at');

        $validator
            ->dateTime('saved_at');

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
        $rules->add($rules->isUnique(['feed_item_id', 'user_id']), ['errorField' => 'feed_item_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['feed_item_id'], 'FeedItems'), ['errorField' => 'feed_item_id']);

        return $rules;
    }
}
