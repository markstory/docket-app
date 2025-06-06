<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * ApiTokens Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\ApiToken newEmptyEntity()
 * @method \App\Model\Entity\ApiToken newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ApiToken[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ApiToken get($primaryKey, $options = [])
 * @method \App\Model\Entity\ApiToken findOrCreate($search, array|callable|null $callback = null, $options = [])
 * @method \App\Model\Entity\ApiToken patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ApiToken[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ApiToken|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ApiToken saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<mixed, \App\Model\Entity\ApiToken>|false saveMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\ApiToken> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\ApiToken>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<mixed, \App\Model\Entity\ApiToken> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ApiTokensTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('api_tokens');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('token')
            ->maxLength('token', 255)
            ->requirePresence('token', 'create')
            ->notEmptyString('token');

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
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }

    public function findByToken(Query $query, $token): Query
    {
        $query->where(['ApiTokens.token' => $token]);

        return $query;
    }

    public function generateApiToken(User $user)
    {
        $apiToken = $this->newEmptyEntity();

        // Fixate userid to the current user.
        $apiToken->user_id = $user->id;
        $apiToken->token = Text::uuid();
        $apiToken->last_used = null;
        $this->save($apiToken);

        return $apiToken;
    }
}
