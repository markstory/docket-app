<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\CalendarProvidersController Test Case
 */
class CalendarProvidersControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.CalendarSources',
        'app.CalendarProviders',
        'app.Projects',
        'app.Users',
    ];

    /**
     * @var \App\Model\Table\CalendarProvidersTable
     */
    protected $CalendarProviders;

    public function setUp(): void
    {
        parent::setUp();

        $this->CalendarProviders = $this->fetchTable('CalendarProviders');
    }

    /**
     * Test create from mobile
     *
     * @vcr googleoauth_callback.yml
     */
    public function testCreateFromGoogle(): void
    {
        $this->loginApi(1);

        $this->post('/api/calendars/google/new', [
            'accessToken' => 'goog-access-token',
            'refreshToken' => 'goog-refresh-token',
        ]);
        $this->assertResponseOk();

        $provider = $this->viewVariable('provider');
        $this->assertNotEmpty($provider);
        $this->assertNotEmpty($provider->identifier);
        $this->assertEquals($provider->access_token, 'goog-access-token');
        $this->assertEquals($provider->refresh_token, 'goog-refresh-token');
    }

    /**
     * Test create from mobile
     *
     * @vcr googleoauth_callback_invalid.yml
     */
    public function testCreateFromGoogleInvalidCredential(): void
    {
        $this->loginApi(1);

        $this->post('/api/calendars/google/new', [
            'accessToken' => 'goog-access-token',
            'refreshToken' => 'goog-refresh-token',
        ]);
        $this->assertResponseCode(400);
    }

    /**
     * Test index method
     *
     * @vcr controller_calendarsources_add.yml
     * @return void
     */
    public function testIndex(): void
    {
        // Owned by a different user.
        $this->makeCalendarProvider(2, 'other@example.com');
        $ownProvider = $this->makeCalendarProvider(1, 'owner@example.com');
        $this->loginApi(1);

        $this->get('/api/calendars');
        $this->assertResponseOk();
        $records = $this->viewVariable('providers');

        $this->assertCount(1, $records);
        $this->assertEquals($ownProvider->id, $records[0]->id);
    }

    /**
     * Test index method
     *
     * @vcr controller_calendarsources_add.yml
     * @return void
     */
    public function testIndexApi(): void
    {
        // Owned by a different user.
        $provider = $this->makeCalendarProvider(1, 'other@example.com');
        $this->loginApi(1);

        $this->get('/api/calendars');
        $this->assertResponseOk();
        $records = $this->viewVariable('providers');

        $this->assertCount(1, $records);
        $this->assertEquals($provider->id, $records[0]->id);
    }

    /**
     * Test view
     *
     * @vcr controller_calendarsources_add.yml
     * @return void
     */
    public function testView(): void
    {
        // Owned by a different user.
        $this->makeCalendarProvider(2, 'other@example.com');
        $ownProvider = $this->makeCalendarProvider(1, 'owner@example.com');
        $this->makeCalendarSource($ownProvider->id, 'primary', [
            'provider_id' => $ownProvider->id,
        ]);
        $this->loginApi(1);

        $this->get("/api/calendars/{$ownProvider->id}/view");
        $this->assertResponseOk();
        $provider = $this->viewVariable('provider');

        $this->assertEquals($ownProvider->id, $provider->id);
        $this->assertNotEmpty($provider->calendar_sources);
    }

    /**
     * Test view permissions
     *
     * @return void
     */
    public function testViewPermissions(): void
    {
        // Owned by a different user.
        $provider = $this->makeCalendarProvider(2, 'other@example.com');
        $this->loginApi(1);

        $this->get("/api/calendars/{$provider->id}/view");
        $this->assertResponseError();
    }

    /**
     * Test view with broken auth
     *
     * @vcr controller_calendarsources_add_auth_fail.yml
     * @return void
     */
    public function testViewBrokenGoogleAuth(): void
    {
        // Owned by a different user.
        $this->makeCalendarProvider(2, 'other@example.com');
        $ownProvider = $this->makeCalendarProvider(1, 'owner@example.com');
        $this->makeCalendarSource($ownProvider->id, 'primary', [
            'provider_id' => $ownProvider->id,
        ]);
        $this->loginApi(1);

        $this->get("/api/calendars/{$ownProvider->id}/view");
        $this->assertResponseOk();
        $provider = $this->viewVariable('provider');

        $this->assertEquals($ownProvider->id, $provider->id);
        $this->assertTrue($provider->broken_auth);
        $this->assertEquals([], $this->viewVariable('calendars'));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $provider = $this->makeCalendarProvider(1, 'owner@example.com');
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/delete");
        $this->assertResponseOk();
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeletePermissions(): void
    {
        $provider = $this->makeCalendarProvider(2, 'other@example.com');
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/delete");
        $this->assertResponseCode(403);
    }
}
