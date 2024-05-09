<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

use function Cake\Collection\collection;

/**
 * App\Controller\CalendarProvidersController Test Case
 *
 * @uses \App\Controller\CalendarProvidersController
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
     */
    public function testCreateFromGoogle(): void
    {
        $this->loadResponseMocks('googleoauth_callback.yml');
        $this->login();
        $this->enableCsrfToken();

        $this->post('/calendars/google/new', [
            'accessToken' => 'goog-access-token',
            'refreshToken' => 'goog-refresh-token',
        ]);
        $this->assertRedirect(['_name' => 'calendarproviders:index']);

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
        $this->login();
        $this->enableCsrfToken();

        $this->post('/calendars/google/new', [
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

        $this->login();
        $this->get('/calendars');
        $this->assertResponseOk();
        $records = $this->viewVariable('providers');

        $this->assertCount(1, $records);
        $this->assertEquals($ownProvider->id, $records[0]->id);
    }

    public function testIndexIncludeLinkedAndUnlinked(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);

        $this->login();
        $this->get("/calendars/?provider={$provider->id}");
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('referer'));
        $resultProvider = $this->viewVariable('activeProvider');
        $this->assertSame($provider->identifier, $resultProvider->identifier);
        $this->assertCount(1, $resultProvider->calendar_sources);
        $this->assertEquals($source->id, $resultProvider->calendar_sources[0]->id);

        $unlinked = $this->viewVariable('unlinked');
        $this->assertCount(1, $unlinked);
        $this->assertEquals('Birthdays Calendar', $unlinked[0]->name);
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

        $this->login();
        $this->enableCsrfToken();
        $this->disableErrorHandlerMiddleware();
        $this->post("/calendars/{$ownProvider->id}/sync");
        $this->assertRedirect('/calendars');
        $provider = $this->viewVariable('provider');

        $this->assertCount(2, $provider->calendar_sources);
        $sourceNames = collection($provider->calendar_sources)->extract('name')->toArray();
        $this->assertContains('Birthdays Calendar', $sourceNames);
        $this->assertContains('Primary Calendar', $sourceNames);
        $this->assertFalse($provider->calendar_sources[0]->synced);
        $this->assertFalse($provider->calendar_sources[1]->synced);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $provider = $this->makeCalendarProvider(1, 'owner@example.com');

        $this->login();
        $this->enableCsrfToken();
        $this->post("/calendars/{$provider->id}/delete");
        $this->assertRedirect('/calendars');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeletePermissions(): void
    {
        $provider = $this->makeCalendarProvider(2, 'other@example.com');

        $this->login();
        $this->enableCsrfToken();
        $this->post("/calendars/{$provider->id}/delete");
        $this->assertResponseCode(403);
    }
}
