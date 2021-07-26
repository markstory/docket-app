<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\CalendarService;
use App\Service\CalendarServiceProvider;
use App\Test\TestCase\FactoryTrait;
use Cake\Core\Container;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;

class CalendarServiceTest extends TestCase
{
    use FactoryTrait;

    public $fixtures = [
        'app.Users',
        'app.CalendarProviders',
        'app.CalendarSources',
        'app.CalendarSubscriptions',
        'app.CalendarItems',
    ];

    /**
     * @var \App\Service\CalendarService
     */
    private $calendar;

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    private $calendarSources;

    /**
     * @var \App\Model\Table\CalendarProvidersTable
     */
    private $calendarItems;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadRoutes();

        $container = new Container();
        $container->addServiceProvider(new CalendarServiceProvider());
        $this->calendar = $container->get(CalendarService::class);
        $this->calendarSources = TableRegistry::get('CalendarSources');
        $this->calendarItems = TableRegistry::get('CalendarItems');

        FrozenTime::setTestNow('2021-07-11 12:13:14');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FrozenTime::setTestNow(null);
    }

    protected function getItems($source)
    {
        return $this->calendarItems->find()
            ->where(['calendar_source_id' => $source->id])
            ->orderAsc('title')
            ->toArray();
    }

    /**
     * @vcr controller_calendarsources_sync.yml
     */
    public function testSyncCreateNew()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);

        $this->calendar->syncEvents($source);
        $items = $this->getItems($source);
        $this->assertCount(3, $items);

        $timed = $items[0];
        $this->assertSame('Dentist Appointment', $timed->title);
        $this->assertNotEmpty($timed->html_link);
        $this->assertNull($timed->start_date);
        $this->assertNull($timed->end_date);

        // Stored times are in UTC,
        $this->assertSame(
            '2021-07-22 16:13:14',
            $timed->start_time->toDateTimeString()
        );
        $this->assertSame(
            'UTC',
            $timed->start_time->getTimeZone()->getName()
        );
        $this->assertSame(
            '2021-07-22 17:13:14',
            $timed->end_time->toDateTimeString()
        );
        $this->assertSame(
            'UTC',
            $timed->end_time->getTimeZone()->getName()
        );

        $allDay = $items[1];
        $this->assertSame('Go camping', $allDay->title);
        $this->assertNull($allDay->start_time);
        $this->assertNull($allDay->end_time);
        $this->assertSame(
            '2021-07-15',
            $allDay->start_date->toDateString()
        );
        $this->assertSame(
            '2021-07-17',
            $allDay->end_date->toDateString()
        );

        $this->assertSame('Moving Day', $items[2]->title);
    }

    /**
     * @vcr controller_calendarsources_sync.yml
     */
    public function testSyncUpdateExisting()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $existing = $this->makeCalendarItem($source->id, [
            'provider_id' => 'calendar-event-1',
            'title' => 'old',
            'html_link' => 'old',
            'start_time' => '2019-01-01 12:13:14',
            'end_time' => '2019-01-01 13:13:14',
        ]);
        $this->calendar->syncEvents($source);
        $items = $this->getItems($source);
        $this->assertCount(3, $items);

        $updated = $items[0];
        $this->assertNotEquals($existing->title, $updated->title);
        $this->assertNotEquals($existing->html_link, $updated->html_link);
        $this->assertSame($existing->provider_id, $updated->provider_id);
        $this->assertNotEquals(
            $existing->start_time->toDateString(),
            $updated->start_time->toDateString()
        );
    }

    /**
     * @vcr controller_calendarsources_sync.yml
     */
    public function testSyncRemoveCancelled()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $remove = $this->makeCalendarItem($source->id, [
            'provider_id' => 'calendar-event-4',
            'title' => 'old',
            'html_link' => 'old',
            'start_time' => '2019-01-01 12:13:14',
            'end_time' => '2019-01-01 13:13:14',
        ]);
        $this->calendar->syncEvents($source);
        $items = $this->getItems($source);

        $this->assertCount(3, $items);
        $ids = collection($items)->extract('provider_id')->toList();
        $this->assertNotContains($remove->provider_id, $ids);
    }

    /**
     * @vcr controller_calendarsources_sync_pagination.yml
     */
    public function testSyncMultiplePages()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $remove = $this->makeCalendarItem($source->id, [
            'provider_id' => 'calendar-event-4',
            'title' => 'old',
            'html_link' => 'old',
            'start_time' => '2019-01-01 12:13:14',
            'end_time' => '2019-01-01 13:13:14',
        ]);

        $this->calendar->syncEvents($source);
        $items = $this->getItems($source);

        $this->assertCount(2, $items);
        $ids = collection($items)->extract('provider_id')->toList();
        $this->assertNotContains($remove->provider_id, $ids);
    }

    /**
     * @vcr controller_calendarsources_sync.yml
     */
    public function testSyncUpdateSyncToken()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->calendar->syncEvents($source);
        $source = $this->calendarSources->get($source->id);
        $this->assertSame('next-sync-token', $source->sync_token);
    }

    /**
     * @vcr controller_calendarsources_add.yml
     */
    public function testListUnsyncedCalendars()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $results = $this->calendar->listUnlinkedCalendars([$source]);
        $this->assertCount(1, $results);
        $new = $results[0];
        $this->assertNull($new->id, 'should be a new record.');
        $this->assertNotNull($new->provider_id);
        $this->assertNotNull($new->name);
    }

    public function testGetSourceForSubscription()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $sub = $this->makeCalendarSubscription($source->id);
        $found = $this->calendar->getSourceForSubscription($sub->identifier, $sub->verifier);

        $this->assertNotEmpty($found);
        $this->assertEquals($source->id, $found->id);
        $this->assertEquals($source->provider_id, $found->provider_id);

        // Has the provider loaded.
        $this->assertNotEmpty($found->calendar_provider);
        $this->assertEquals($provider->id, $found->calendar_provider->id);
        $this->assertEquals($provider->identifier, $found->calendar_provider->identifier);
    }

    public function testGetSourceForSubscriptionInvalid()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->expectException(RecordNotFoundException::class);
        $this->calendar->getSourceForSubscription('nope', 'also nope');
    }

    public function testGetSourceForSubscriptionInvalidVerifier()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $sub = $this->makeCalendarSubscription($source->id);
        $this->expectException(RecordNotFoundException::class);
        $this->calendar->getSourceForSubscription($sub->identifier, 'nope');
    }

    /**
     * @vcr calendarservice_createsubscription_success.yml
     */
    public function testCreateSubscriptionSuccess()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $sub = $this->calendar->createSubscription($source, 'random-string', 'verifier-string');
        $this->assertNotEmpty($sub);
        $this->assertSame($source->id, $sub->calendar_source_id);
        $this->assertNotEmpty($sub->identifier);
        $this->assertNotEmpty($sub->verifier);
    }

    /**
     * @vcr calendarservice_createsubscription_failure.yml
     */
    public function testCreateSubscriptionFailure()
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->expectException(RuntimeException::class);
        $this->calendar->createSubscription($source, 'random-string', 'verifier-string');
    }
}
