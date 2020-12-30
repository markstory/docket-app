<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\IdentityInterface as AuthenticationIdentity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityInterface as AuthorizationIdentity;
use Authorization\Policy\ResultInterface;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Project[] $projects
 * @property \App\Model\Entity\TodoComment[] $todo_comments
 */
class User extends Entity implements AuthenticationIdentity, AuthorizationIdentity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'name' => true,
        'unverified_email' => true,
        'modified' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'password',
        'email_verified',
    ];

    protected $_virtual = ['avatar_hash'];

    protected function _getAvatarHash()
    {
        return md5(strtolower($this->email));
    }

    /**
     * Hash password
     *
     * @param string $password
     * @return string|null
     */
    protected function _setPassword(string $password) : ?string
    {
        if (mb_strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
    }

    public function getIdentifier(): int
    {
        return $this->get('id');
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function can($action, $resource): bool
    {
        return $this->authorization->can($this, $action, $resource);
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function canResult($action, $resource): ResultInterface
    {
        return $this->authorization->canResult($this, $action, $resource);
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function applyScope($action, $resource)
    {
        return $this->authorization->applyScope($this, $action, $resource);
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function getOriginalData()
    {
        return $this;
    }

    /**
     * Setter to be used by the middleware.
     */
    public function setAuthorization(AuthorizationServiceInterface $service)
    {
        $this->authorization = $service;

        return $this;
    }
}
