<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 *
 * @uses \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Users',
        'app.Projects',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEditLoginRequired(): void
    {
        $this->enableCsrfToken();
        $this->post('/users/profile', [
            'unverified_email' => 'example@example.com'
        ]);
        $this->assertRedirectContains('/login');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->post('/users/profile', [
            'email' => 'badthings@example.com',
            'unverified_email' => 'example@example.com',
            'timezone' => 'America/San_Francisco',
        ]);
        $this->assertRedirect('/todos/today');
        $user = $this->Users->get(1);
        $this->assertNotEquals('badthings@example.com', $user->email);
        $this->assertTrue($user->email_verified);
        $this->assertEquals('example@example.com', $user->unverified_email);
    }
}
