<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Calendar\Controller\GoogleOauthController;
use Calendar\Model\Table\CalendarProvidersTable;

/**
 * App\Controller\GoogleOauthController Test Case
 *
 * @uses \App\Controller\GoogleOauthController
 */
class GoogleOauthControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    protected CalendarProvidersTable $CalendarProviders;

    /**
     * @var array
     */
    protected array $fixtures = [
        'app.CalendarProviders',
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->CalendarProviders = $this->fetchTable('Calendar.CalendarProviders');
    }

    /**
     * Test authorize method
     *
     * @return void
     */
    public function testAuthorize(): void
    {
        $this->login();
        $this->get('/auth/google/authorize');
        $this->assertRedirectContains('accounts.google.com');
    }

    public function testAuthorizeMobile(): void
    {
        $token = $this->makeApiToken(1);
        $this->useApiToken($token->token);

        $this->post('/auth/google/authorize?mobile=1');
        $this->assertRedirectContains('accounts.google.com');
        $this->assertSessionHasKey('Auth');
        $this->assertSession(1, 'Auth.id');
        $this->assertSessionHasKey(GoogleOauthController::MOBILE_VIEW);
    }

    public function testAuthorizeRequireLogin(): void
    {
        $this->get('/auth/google/authorize');
        $this->assertRedirectContains('/login');
    }

    /**
     * Test callback method
     */
    public function testCallbackSuccess(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->loadResponseMocks('googleoauth_callback.yml');
        $this->login();
        $this->get('/auth/google/callback?code=auth-code');

        $this->assertRedirect(['_name' => 'calendarproviders:index']);
        $provider = $this->CalendarProviders
            ->find()
            ->where(['CalendarProviders.user_id' => 1])
            ->firstOrFail();
        $this->assertSame('George Goggles (goog@example.com)', $provider->display_name);
        $this->assertNotEmpty($provider->identifier);
        $this->assertNotEmpty($provider->access_token);
        $this->assertNotEmpty($provider->refresh_token);
        $this->assertNotEmpty($provider->token_expiry);
    }

    /**
     * Test callback method with mobile response
     */
    public function testCallbackSuccessMobile(): void
    {
        $this->loadResponseMocks('googleoauth_callback.yml');
        $user = $this->fetchTable('Users')->get(1);
        $this->session([
            'Auth' => $user,
            GoogleOauthController::MOBILE_VIEW => true,
        ]);
        $this->get('/auth/google/callback?code=auth-code');

        $this->assertResponseOk();
        $this->assertResponseContains('Connection Complete');
        $provider = $this->CalendarProviders
            ->find()
            ->where(['CalendarProviders.user_id' => 1])
            ->firstOrFail();
        $this->assertSame('George Goggles (goog@example.com)', $provider->display_name);
        $this->assertNotEmpty($provider->identifier);
        $this->assertNotEmpty($provider->access_token);
        $this->assertNotEmpty($provider->refresh_token);
        $this->assertNotEmpty($provider->token_expiry);
    }

    /**
     * Test callback method
     */
    public function testCallbackNoRefreshToken(): void
    {
        $this->loadResponseMocks('googleoauth_callback_norefresh.yml');
        $this->login();
        $this->enableRetainFlashMessages();
        $this->get('/auth/google/callback?code=auth-code');

        $this->assertRedirect(['_name' => 'calendarproviders:index']);
        $exists = $this->CalendarProviders
            ->find()
            ->where(['CalendarProviders.user_id' => 1])
            ->count();
        $this->assertEquals(0, $exists, 'No provider made as token was bad');
        $this->assertFlashElement('flash/error');
    }
}
