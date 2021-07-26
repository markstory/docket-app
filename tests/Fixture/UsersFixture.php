<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\Auth\DefaultPasswordHasher;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'name' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null],
        'email' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'password' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'email_verified' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'unverified_email' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null],
        'timezone' => ['type' => 'string', 'default' => 'UTC'],
        'theme' => ['type' => 'string', 'default' => 'light'],
        'created' => ['type' => 'timestamp', 'length' => null, 'precision' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => ''],
        'modified' => ['type' => 'timestamp', 'length' => null, 'precision' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => ''],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
    ];
    // phpcs:enable
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
