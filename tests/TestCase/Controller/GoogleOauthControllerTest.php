<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\GoogleOauthController Test Case
 *
 * @uses \App\Controller\GoogleOauthController
 */
class GoogleOauthControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * @var array
     */
    protected $fixtures = [
        'app.CalendarProviders',
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->CalendarProviders = TableRegistry::get('CalendarProviders');
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

    public function testAuthorizeRequireLogin(): void
    {
        $this->get('/auth/google/authorize');
        $this->assertRedirectContains('/login');
    }

    /**
     * Test callback method
     *
     * @vcr googleoauth_callback.yml
     */
    public function testCallbackSuccess(): void
    {
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
}
