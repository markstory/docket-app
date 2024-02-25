<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Table\UsersTable;
use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\EmailTrait;
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
    use EmailTrait;
    use IntegrationTestTrait;

    public UsersTable $Users;

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
        $this->Users = $this->fetchTable('Users');
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
            'unverified_email' => 'example@example.com',
        ]);
        $this->assertRedirectContains('/login');
    }

    public function testEditGetApi(): void
    {
        $this->loginApi(1);

        $this->post('/api/users/profile');
        $this->assertResponseOk();

        $user = $this->viewVariable('user');
        $this->assertEquals('Mark', $user->name);
        $this->assertEquals('light', $user->theme);
    }

    public function testEditApi(): void
    {
        $this->loginApi(1);

        $this->post('/api/users/profile', [
            'name' => 'tester mc testerson',
            'timezone' => 'America/New_York',
            'theme' => 'dark',
        ]);
        $this->assertResponseOk();

        $user = $this->viewVariable('user');
        $this->assertEquals('tester mc testerson', $user->name);
        $this->assertEquals('dark', $user->theme);

        $user = $this->Users->get(1);
        $this->assertNotEquals('badthings@example.com', $user->email);
        $this->assertEquals('dark', $user->theme);
    }

    public function testEditNoEmailChange(): void
    {
        $this->loginApi(1);
        $this->post('/api/users/profile', [
            'unverified_email' => '',
            'theme' => 'dark',
            'referer' => '/tasks/today',
        ]);

        $this->assertResponseOk();
        $user = $this->Users->get(1);
        $this->assertTrue($user->email_verified);
        $this->assertMailCount(0);
    }

    public function testNewPasswordResetPostOk()
    {
        $user = $this->Users->get(1);
        $token = $user->passwordResetToken();

        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post("/password/new/{$token}", [
            'password' => 'super sekret tech',
            'confirm_password' => 'super sekret tech',
        ]);
        $this->assertRedirect('/login');
        $this->assertFlashElement('flash/success');
        $update = $this->Users->get($user->id);
        $this->assertNotEquals($user->password, $update->password);
    }
}
