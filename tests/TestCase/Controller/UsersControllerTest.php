<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
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

    public function testLogin()
    {
        $this->enableCsrfToken();
        $this->post('/login', [
            'email' => 'mark@example.com',
            'password' => 'password123',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertSession('mark@example.com', 'Auth.email');
    }

    public function testLoginUpdateTimezone()
    {
        $this->enableCsrfToken();
        $this->post('/login', [
            'email' => 'mark@example.com',
            'password' => 'password123',
            'timezone' => 'America/New_York',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertSession('mark@example.com', 'Auth.email');

        $user = $this->Users->get(1);
        $this->assertSame('America/New_York', $user->timezone);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAddValidationErrors(): void
    {
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/add', [
            'name' => 'Sally Example',
            'email' => 'not-valid',
            'password' => 'password',
            'confirm_password' => 'password',
            'timezone' => 'UTC',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertNotEmpty($this->viewVariable('errors'));
        $this->assertMailCount(0);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAddSuccess(): void
    {
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/add', [
            'name' => 'Wally Example',
            'email' => 'wally@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'timezone' => 'UTC',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertFlashElement('flash/success');

        $this->assertMailCount(1);
        $this->assertMailSubjectContains('Verify your email');

        $user = $this->Users->findByEmail('wally@example.com')->firstOrFail();
        $project = $this->Users->Projects->findByUserId($user->id)->firstOrFail();
        $this->assertNotEmpty($project->name);
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
            'timezone' => 'America/New_York',
            'theme' => 'dark',
            'referer' => '/tasks/today',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertFlashElement('flash/success');
        $user = $this->Users->get(1);
        $this->assertNotEquals('badthings@example.com', $user->email);
        $this->assertTrue($user->email_verified);
        $this->assertEquals('example@example.com', $user->unverified_email);
        $this->assertEquals('dark', $user->theme);

        $this->assertMailCount(1);
        $this->assertMailSubjectContains('Verify your email');
    }

    public function testEditGetApi(): void
    {
        $token = $this->makeApiToken(1);
        $this->useApiToken($token->token);
        $this->requestJson();

        $this->post('/users/profile');
        $this->assertResponseOk();
        $user = $this->viewVariable('user');
        $this->assertEquals('Mark', $user->name);
        $this->assertEquals('light', $user->theme);
    }

    public function testEditApi(): void
    {
        $token = $this->makeApiToken(1);
        $this->useApiToken($token->token);
        $this->requestJson();

        $this->post('/users/profile', [
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
        $this->login();
        $this->enableCsrfToken();
        $this->post('/users/profile', [
            'unverified_email' => '',
            'theme' => 'dark',
            'referer' => '/tasks/today',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertFlashElement('flash/success');
        $user = $this->Users->get(1);
        $this->assertTrue($user->email_verified);

        $this->assertMailCount(0);
    }

    public function testUpdatePasswordRequiresLogin()
    {
        $this->enableCsrfToken();
        $this->post('/users/updatePassword', [
            'current_password' => 'password12',
            'password' => 'password124',
            'confirm_password' => 'password124',
        ]);
        $this->assertRedirectContains('/login');
    }

    public function testUpdatePasswordCurrentMustMatch()
    {
        $user = $this->Users->get(1);
        $user->password = 'password123';
        $this->Users->save($user);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/updatePassword', [
            'current_password' => 'password12',
            'password' => 'new-password',
            'confirm_password' => 'new-password',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testUpdatePasswordNewMustMatch()
    {
        $user = $this->Users->get(1);
        $user->password = 'password123';
        $this->Users->save($user);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/updatePassword', [
            'current_password' => 'password123',
            'password' => 'new password',
            'confirm_password' => 'not password',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testUpdatePasswordCurrentRequired()
    {
        $user = $this->Users->get(1);
        $user->password = 'password123';
        $this->Users->save($user);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/updatePassword', [
            'password' => 'new password',
            'confirm_password' => 'not password',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testUpdatePasswordSuccess()
    {
        $user = $this->Users->get(1);
        $user->password = 'password123';
        $this->Users->save($user);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post('/users/updatePassword', [
            'current_password' => 'password123',
            'password' => 'new password',
            'confirm_password' => 'new password',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertFlashElement('flash/success');
    }

    public function testVerifyEmailInvalidTokenFormat()
    {
        $this->login();
        $this->enableRetainFlashMessages();
        $this->get('/users/verifyEmail/N0t?base?64');
        $this->assertRedirect('/login');
        $this->assertFlashElement('flash/error');
    }

    public function testVerifyEmailInvalidTokenPayload()
    {
        $token = base64_encode(json_encode(['uid' => 1]));

        $this->login();
        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");
        $this->assertRedirect('/login');
        $this->assertFlashElement('flash/error');
    }

    public function testVerifyEmailMismatchEmail()
    {
        $user = $this->Users->get(1);
        $user->unverified_email = 'newer@example.com';
        $token = $user->emailVerificationToken();

        $this->login();
        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");
        $this->assertRedirect('/login');
        $this->assertFlashElement('flash/error');
    }

    public function testVerifyEmailRequireLogin()
    {
        $user = $this->Users->get(1);
        $user->unverified_email = 'newer@example.com';
        $this->Users->save($user);
        $token = $user->emailVerificationToken();

        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");

        $this->assertRedirectContains('/login');
        $user = $this->Users->get(1);
        $this->assertNotEmpty($user->unverified_email);
        $this->assertNotEquals('newer@example.com', $user->email);
    }

    public function testVerifyEmailSuccess()
    {
        $user = $this->Users->get(1);
        $user->unverified_email = 'newer@example.com';
        $this->Users->save($user);
        $token = $user->emailVerificationToken();

        $this->login();
        $this->enableRetainFlashMessages();
        $this->get("/users/verifyEmail/{$token}");

        $this->assertRedirect('/tasks/today');
        $user = $this->Users->get(1);
        $this->assertEquals('', $user->unverified_email);
        $this->assertEquals('newer@example.com', $user->email);
        $this->assertTrue($user->email_verified);
        $this->assertFlashElement('flash/success');
    }

    public function testPasswordResetGet()
    {
        $this->get('/password/reset');
        $this->assertResponseOk();
    }

    public function testPasswordResetPostNoMatch()
    {
        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post('/password/reset', [
            'email' => 'nosuch@user.com',
        ]);
        // Should quack like it worked.
        $this->assertResponseOk();
        $this->assertFlashElement('flash/success');
        $this->assertMailCount(0);
    }

    public function testPasswordResetPostMatch()
    {
        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post('/password/reset', [
            'email' => 'mark@example.com',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/success');

        $this->assertMailCount(1);
        $this->assertMailSentTo('mark@example.com');
        $this->assertMailSubjectContains('Password');
        $this->assertMailContainsText('/password/new/');
    }

    public function testNewPasswordResetGetTokenBadFormat()
    {
        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->get('/password/new/not-good-data');
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testNewPasswordResetGetTokenExpired()
    {
        FrozenTime::setTestNow(new FrozenTime('-6 hours'));
        $user = $this->Users->get(1);
        $token = $user->passwordResetToken();
        FrozenTime::setTestNow(null);

        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->get("/password/new/{$token}");
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testNewPasswordResetGetTokenOk()
    {
        $user = $this->Users->get(1);
        $token = $user->passwordResetToken();

        $this->enableCsrfToken();
        $this->get("/password/new/{$token}");
        $this->assertResponseOk();
    }

    public function testNewPasswordResetPostMissingFields()
    {
        $user = $this->Users->get(1);
        $token = $user->passwordResetToken();

        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post("/password/new/{$token}", [
            'password' => 'super sekret',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testNewPasswordResetPostValidationError()
    {
        $user = $this->Users->get(1);
        $token = $user->passwordResetToken();

        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post("/password/new/{$token}", [
            'password' => 'super sekret',
            'confirm_password' => 'super bad',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
    }

    public function testNewPasswordResetPostExpiredToken()
    {
        FrozenTime::setTestNow(new FrozenTime('-6 hours'));
        $user = $this->Users->get(1);
        $token = $user->passwordResetToken();
        FrozenTime::setTestNow(null);

        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post("/password/new/{$token}", [
            'password' => 'super sekret tech',
            'confirm_password' => 'super sekret tech',
        ]);
        $this->assertResponseOk();
        $this->assertFlashElement('flash/error');
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
