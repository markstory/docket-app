<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\CalendarSourcesController Test Case
 */
class CalendarSourcesControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * @var string[]
     */
    protected array $fixtures = [
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
        $this->Users = $this->fetchTable('Users');
        $this->CalendarSources = $this->fetchTable('CalendarSources');
        $this->CalendarItems = $this->fetchTable('CalendarItems');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DateTime::setTestNow(null);
    }

    public function testSyncApi(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->loadResponseMocks('controller_calendarsources_sync.yml');
        DateTime::setTestNow('2032-07-11 12:13:14');

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/sync");

        $source = $this->viewVariable('source');
        $this->assertNotEmpty($source);

        $result = $this->CalendarItems->find()->where([
            'CalendarItems.calendar_source_id' => $source->id,
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

    public function testSyncReplaceExistingRemoveDeleted(): void
    {
        $this->loadResponseMocks('controller_calendarsources_sync.yml');
        DateTime::setTestNow('2032-07-11 12:13:14');

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
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/sync");

        $this->assertResponseOk();
        $this->assertFalse($this->CalendarItems->exists(['id' => $remove->id]));

        $updated = $this->CalendarItems->findById($update->id)->first();
        $this->assertNull($updated);

        $created = $this->CalendarItems->findByTitle('Dentist Appointment')->firstOrFail();
        $this->assertNotNull($created);
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
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/delete");

        $this->assertResponseCode(204);
        $this->assertFalse($this->CalendarSources->exists(['CalendarSources.id' => $source->id]));
    }

    /**
     * Test delete cancel subscription
     */
    public function testDeleteCancelSubscription(): void
    {
        $this->loadResponseMocks('controller_calendarsources_delete.yml');
        $user = $this->Users->get(1);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);
        $this->makeCalendarSubscription($source->id, 'subscription-id');
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/delete");

        $this->assertResponseCode(204);
        $this->assertFalse($this->CalendarSources->exists(['CalendarSources.id' => $source->id]));
    }

    /**
     * Test delete method
     */
    public function testDeleteNoPermission(): void
    {
        $user = $this->Users->get(2);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/delete");

        $this->assertResponseCode(403);
    }

    public function testAddPost()
    {
        $this->loadResponseMocks('controller_calendarsources_add_post.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');

        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/add", [
            'provider_id' => 'calendar-1',
            'color' => 1,
            'name' => 'Work Calendar',
        ]);
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('source'));
        $source = $this->CalendarSources->findByName('Work Calendar')->firstOrFail();
        $this->assertSame('calendar-1', $source->provider_id);

        $subs = $this->fetchTable('CalendarSubscriptions');
        $sub = $subs->findByCalendarSourceId($source->id)->firstOrFail();
        $this->assertNotEmpty($sub->identifier);
    }

    public function testAddPostSubscriptionFail()
    {
        $this->loadResponseMocks('controller_calendarsources_add_post_fail.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');

        $this->login();
        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/add", [
            'provider_id' => 'calendar-1',
            'color' => 1,
            'name' => 'Work Calendar',
        ]);
        $this->assertRedirect("/calendars?provider={$provider->id}");
        $this->assertFlashElement('flash/error');

        $source = $this->CalendarSources->findByName('Work Calendar')->firstOrFail();
        $this->assertSame('calendar-1', $source->provider_id);

        $subs = $this->fetchTable('CalendarSubscriptions');
        $this->assertEmpty($subs->findByCalendarSourceId($source->id)->first());
    }

    /**
     * Test edit with api token method
     *
     * @return void
     */
    public function testEditApi(): void
    {
        $user = $this->Users->get(1);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/edit", [
            'color' => 3,
            'name' => 'new values',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('new values');
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
        $this->loginApi(1);

        $this->post("/api/calendars/{$provider->id}/sources/{$source->id}/edit", [
            'color' => 3,
            'name' => 'new values',
        ]);
        $this->assertResponseCode(403);
    }
}
