<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
    protected $fixtures = [
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

        $this->CalendarProviders = TableRegistry::get('CalendarProviders');
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

        $this->login();
        $this->get('/calendars');
        $this->assertResponseOk();
        $records = $this->viewVariable('calendarProviders');

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
        $token = $this->makeApiToken();
        // Owned by a different user.
        $provider = $this->makeCalendarProvider(1, 'other@example.com');

        $this->requestJson();
        $this->useApiToken($token);

        $this->get('/calendars');
        $this->assertResponseOk();
        $records = $this->viewVariable('calendarProviders');

        $this->assertCount(1, $records);
        $this->assertEquals($provider->id, $records[0]->id);
    }

    /**
     * @vcr controller_calendarsources_add.yml
     */
    public function testIndexIncludeLinkedAndUnlinked(): void
    {
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

        $this->login();
        $this->get("/calendars/{$ownProvider->id}/view");
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
        $this->login();
        $this->get("/calendars/{$provider->id}/view");
        $this->assertResponseError();
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
