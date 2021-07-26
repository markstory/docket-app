<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\CalendarSourcesController Test Case
 */
class CalendarSourcesControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * @var string[]
     */
    protected $fixtures = [
        'app.Users',
        'app.CalendarProviders',
        'app.CalendarSources',
        'app.CalendarSubscriptions',
        'app.CalendarItems',
        'app.Projects',
    ];

    /**
     * @var \App\Model\Table\CalendarItemsTable
     */
    protected $CalendarItems;

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    protected $CalendarSources;

    /**
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
        $this->CalendarSources = TableRegistry::get('CalendarSources');
        $this->CalendarItems = TableRegistry::get('CalendarItems');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        FrozenTime::setTestNow(null);
    }

    /**
     * @vcr controller_calendarsources_sync.yml
     */
    public function testSync(): void
    {
        FrozenTime::setTestNow('2021-07-11 12:13:14');

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/calendars/{$provider->id}/sources/{$source->id}/sync");
        $this->assertRedirect("/calendars/{$provider->id}/sources/add");
        $this->assertFlashElement('flash/success');

        $result = $this->CalendarItems->find()->where([
            'CalendarItems.calendar_source_id' => $source->id
        ])->toArray();
        $this->assertCount(3, $result);
        foreach ($result as $event) {
            $this->assertNotEmpty($event->title);
            $this->assertNotEmpty($event->html_link);
            $this->assertNotEmpty($event->getStart());
            $this->assertNotEmpty($event->getEnd());
        }

        $source = $this->CalendarSources->get($source->id);
        $this->assertSame('next-sync-token', $source->sync_token);
    }

    /**
     * @vcr controller_calendarsources_sync.yml
     */
    public function testSyncUpdateExistingRemoveDeleted(): void
    {
        FrozenTime::setTestNow('2021-07-11 12:13:14');

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $update = $this->makeCalendarItem($source->id, [
            'title' => 'old title',
            'provider_id' => 'calendar-event-1',
        ]);
        $remove = $this->makeCalendarItem($source->id, [
            'title' => 'remove',
            'provider_id' => 'calendar-event-4',
        ]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/calendars/{$provider->id}/sources/{$source->id}/sync");
        $this->assertRedirect("/calendars/{$provider->id}/sources/add");
        $this->assertFlashElement('flash/success');

        $this->assertFalse($this->CalendarItems->exists(['id' => $remove->id]));

        $updated = $this->CalendarItems->get($update->id);
        $this->assertSame('Dentist Appointment', $updated->title);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $user = $this->Users->get(1);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/delete");
        $this->assertRedirect(['_name' => 'calendarsources:add', 'providerId' => $provider->id]);
        $this->assertFalse($this->CalendarSources->exists(['CalendarSources.id' => $source->id]));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeleteNoPermission(): void
    {
        $user = $this->Users->get(2);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/delete");
        $this->assertResponseCode(403);
    }

    public function testAddInvalidProvider(): void
    {
        $this->login();
        $this->get('/calendars/99/sources/add');
        $this->assertResponseCode(404);
    }

    /**
     * @vcr controller_calendarsources_add.yml
     */
    public function testAddIncludeLinkedAndUnlinked(): void
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);

        $this->login();
        $this->get("/calendars/{$provider->id}/sources/add");
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('referer'));
        $resultProvider = $this->viewVariable('calendarProvider');
        $this->assertSame($provider->identifier, $resultProvider->identifier);
        $this->assertCount(1, $resultProvider->calendar_sources);
        $this->assertEquals($source->id, $resultProvider->calendar_sources[0]->id);

        $unlinked = $this->viewVariable('unlinked');
        $this->assertCount(1, $unlinked);
        $this->assertEquals('Birthdays Calendar', $unlinked[0]->name);
    }

    /**
     * @vcr calendarservice_createsubscription_success.yml
     */
    public function testAddPost()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/add", [
            'provider_id' => 'calendar-1',
            'color' => 1,
            'name' => 'Work Calendar',
        ]);
        $this->assertRedirect("/calendars/{$provider->id}/sources/add");
        $this->assertFlashElement('flash/success');

        $source = $this->CalendarSources->findByName('Work Calendar')->firstOrFail();
        $this->assertSame('calendar-1', $source->provider_id);

        $subs = TableRegistry::get('CalendarSubscriptions');
        $sub = $subs->findByCalendarSourceId($source->id)->firstOrFail();
        $this->assertNotEmpty($sub->identifier);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $user = $this->Users->get(1);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/edit", [
            'color' => 3,
            'name' => 'new values',
        ]);
        $this->assertRedirect("/calendars/{$provider->id}/sources/add");
        $this->assertFlashElement('flash/success');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEditPermissions(): void
    {
        $user = $this->Users->get(2);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/edit", [
            'color' => 3,
            'name' => 'new values',
        ]);
        $this->assertResponseCode(403);
    }
}
