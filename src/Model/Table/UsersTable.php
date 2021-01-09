<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTimeZone;
use Exception;

/**
 * Users Model
 *
 * @property \App\Model\Table\ProjectsTable&\Cake\ORM\Association\HasMany $Projects
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
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

        $this->setTable('users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Projects', [
            'foreignKey' => 'user_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->email('unverified_email');

        $validator = $this->validationPassword($validator);

        $validator
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator->scalar('timezone')
                  ->add('timezone', 'validtimezone', [
                      'rule' => function ($value) {
                          try {
                              $tz = new DateTimeZone($value);

                              return true;
                          } catch (Exception $e) {
                              return false;
                          }
                      },
                      'message' => 'Timezone is not valid.',
                  ]);

        return $validator;
    }

    public function validationRegister(Validator $validator): Validator
    {
        $validator
            ->email('email')
            ->requirePresence('email')
            ->notEmptyString('email');

        $validator = $this->validationResetPassword($validator);

        $validator->requirePresence('password');

        // TODO add better timezone validation.
        $validator->scalar('timezone');

        return $validator;
    }

    public function validationPassword(Validator $validator): Validator
    {
        $validator
            ->scalar('password')
            ->minLength('password', 10, __('Passwords must be at least 10 characters long'))
            ->maxLength('password', 255, __('Passwords cannot be longer than 255 characters.'))
            ->requirePresence('password');

        return $validator;
    }

    public function validationResetPassword(Validator $validator): Validator
    {
        $validator = $this->validationPassword($validator);

        $validator
            ->scalar('confirm_password')
            ->equaltoField('confirm_password', 'password', __('Your passwords must match.'))
            ->requirePresence('confirm_password');

        return $validator;
    }

    public function validationUpdatePassword(Validator $validator): Validator
    {
        $validator = $this->validationResetPassword($validator);

        $validator
            ->scalar('current_password')
            ->requirePresence('current_password');

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
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        $rules->addUpdate(function (User $entity) {
            if (!($entity->isDirty('password') && $entity->isDirty('current_password'))) {
                return true;
            }
            $hasher = $entity->passwordHasher();
            return $hasher->check(
                $entity->current_password,
                $entity->getOriginal('password')
            );
        }, 'currentPasswordMatch', [
            'errorField' => 'current_password',
            'message' => __('Your current password is not correct.'),
        ]);

        return $rules;
    }
}
