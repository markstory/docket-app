<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $hasher = new DefaultPasswordHasher();
        $this->records = [
            [
                'name' => 'Mark',
                'email' => 'mark@example.com',
                'password' => $hasher->hash('password123'),
                'email_verified' => true,
                'unverified_email' => '',
                'timezone' => 'UTC',
                'created' => 1604198822,
                'modified' => 1604198822,
            ],
            [
                'name' => 'Sally',
                'email' => 'sally@example.com',
                'password' => $hasher->hash('hunter12abc'),
                'email_verified' => true,
                'unverified_email' => '',
                'timezone' => 'UTC',
                'created' => 1604198822,
                'modified' => 1604198822,
            ],
        ];
        parent::init();
    }
}
