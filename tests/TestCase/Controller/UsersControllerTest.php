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
        $this->assertFlashElement('flash/success');
        $user = $this->Users->get(1);
        $this->assertNotEquals('badthings@example.com', $user->email);
        $this->assertTrue($user->email_verified);
        $this->assertEquals('example@example.com', $user->unverified_email);
    }

    public function testVerifyEmailInvalidTokenFormat()
    {
        $this->login();
        $this->enableRetainFlashMessages();
        $this->get('/users/verifyEmail/N0t?base?64');
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testVerifyEmailInvalidTokenPayload()
    {
        $token = base64_encode(json_encode(['uid' => 1]));

        $this->disableErrorHandlerMiddleware();
        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testVerifyEmailMismatchEmail()
    {
        $user = $this->Users->get(1);
        $user->unverified_email = 'newer@example.com';
        $token = $user->emailVerificationToken();

        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testVerifyEmailSuccess()
    {
        $user = $this->Users->get(1);
        $user->unverified_email = 'newer@example.com';
        $this->Users->save($user);

        $token = $user->emailVerificationToken();
        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");

        $this->assertResponseCode(302);
        $user = $this->Users->get(1);
        $this->assertEquals('', $user->unverified_email);
        $this->assertEquals('newer@example.com', $user->email);
        $this->assertTrue($user->email_verified);
        $this->assertFlashElement('flash/success');
    }
}
