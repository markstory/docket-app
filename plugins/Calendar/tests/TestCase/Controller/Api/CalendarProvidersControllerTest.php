<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use function Cake\Collection\collection;

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
        'plugin.Calendar.CalendarSources',
        'plugin.Calendar.CalendarProviders',
        'plugin.Tasks.Projects',
        'app.Users',
    ];

    /**
     * @var \Calendar\Model\Table\CalendarProvidersTable
     */
    protected $CalendarProviders;

    public function setUp(): void
    {
        parent::setUp();

        $this->CalendarProviders = $this->fetchTable('Calendar.CalendarProviders');
    }

    /**
     * Test create from mobile
     */
    public function testCreateFromGoogle(): void
    {
        $this->loadResponseMocks('googleoauth_callback.yml');
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
     */
    public function testCreateFromGoogleInvalidCredential(): void
    {
        $this->loadResponseMocks('googleoauth_callback_invalid.yml');
        $this->loginApi(1);

        $this->post('/api/calendars/google/new', [
            'accessToken' => 'goog-access-token',
            'refreshToken' => 'goog-refresh-token',
        ]);
        $this->assertResponseCode(400);
    }

    /**
     * Test index method
     */
    public function testIndex(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
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
     */
    public function testIndexApi(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
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
     */
    public function testView(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
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
     */
    public function testViewBrokenGoogleAuth(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add_auth_fail.yml');

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

    /**
     * Test sync method
     */
    public function testSyncAddsSources(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
        // Owned by a different user.
        $this->makeCalendarProvider(2, 'other@example.com');
        $ownProvider = $this->makeCalendarProvider(1, 'owner@example.com');

        $this->loginApi(1);
        $this->post("/api/calendars/{$ownProvider->id}/sync");
        $this->assertResponseOk();
        $this->assertResponseCode(204);

        /** @var \App\Model\Entity\CalendarProvider $provider */
        $provider = $this->viewVariable('provider');

        $provider = $this->CalendarProviders->get($ownProvider->id, contain: 'CalendarSources');
        $this->assertCount(2, $provider->calendar_sources);
        $sourceNames = collection($provider->calendar_sources)->extract('name')->toArray();
        $this->assertContains('Birthdays Calendar', $sourceNames);
        $this->assertContains('Primary Calendar', $sourceNames);
        $this->assertFalse($provider->calendar_sources[0]->synced);
        $this->assertFalse($provider->calendar_sources[1]->synced);
    }

    public function testSyncUpdatesAndRemoves(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
        $provider = $this->makeCalendarProvider(1, 'owner@example.com');
        $keepSource = $this->makeCalendarSource($provider->id, 'keeper', ['provider_id' => 'calendar-1']);
        $remove = $this->makeCalendarSource($provider->id, 'delete me', ['provider_id' => 'remove']);

        $this->loginApi(1);
        $this->post("/api/calendars/{$provider->id}/sync");
        $this->assertResponseCode(204);

        /** @var \App\Model\Entity\CalendarProvider $provider */
        $provider = $this->CalendarProviders->get($provider->id, contain: 'CalendarSources');

        $sources = $provider->calendar_sources;
        $this->assertCount(2, $sources);
        $this->assertEquals($keepSource->id, $sources[0]->id, 'Should exist from before');
        $this->assertEquals('Primary Calendar', $sources[0]->name, 'Should update');
        $this->assertNotEquals($remove->id, $sources[1]->id, 'Remove is not a record anymore.');
    }
}
