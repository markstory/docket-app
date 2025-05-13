<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Service;

use App\Test\TestCase\FactoryTrait;
use Cake\Core\Container;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Calendar\Service\CalendarService;
use Calendar\Service\CalendarServiceProvider;
use RuntimeException;
use function Cake\Collection\collection;

class CalendarServiceTest extends TestCase
{
    use FactoryTrait;

    public array $fixtures = [
        'app.Users',
        'app.CalendarProviders',
        'app.CalendarSources',
        'app.CalendarSubscriptions',
        'app.CalendarItems',
    ];

    /**
     * @var \Calendar\Service\CalendarService
     */
    private $calendar;

    /**
     * @var \Calendar\Model\Table\CalendarSourcesTable
     */
    private $calendarSources;

    /**
     * @var \Calendar\Model\Table\CalendarProvidersTable
     */
    private $calendarItems;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadRoutes();

        $container = new Container();
        $container->addServiceProvider(new CalendarServiceProvider());
        $this->calendar = $container->get(CalendarService::class);
        $this->calendarSources = $this->fetchTable('Calendar.CalendarSources');
        $this->calendarItems = $this->fetchTable('Calendar.CalendarItems');

        DateTime::setTestNow('2032-07-11 12:13:14');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        DateTime::setTestNow(null);
    }

    protected function getItems($source): array
    {
        return $this->calendarItems->find()
            ->where(['calendar_source_id' => $source->id])
            ->orderByAsc('title')
            ->toArray();
    }

    public function testSyncCreateNew(): void
    {
        $this->loadResponseMocks('controller_calendarsources_sync.yml');
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

    public function testSyncUpdateExisting(): void
    {
        $this->loadResponseMocks('controller_calendarsources_sync.yml');
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
        $updated = $this->fetchTable('Calendar.CalendarSources')->get($source->id);
        $this->assertNotNull($updated->last_sync);

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

    public function testSyncRemoveCancelled(): void
    {
        $this->loadResponseMocks('controller_calendarsources_sync.yml');
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

    public function testSyncMultiplePages(): void
    {
        $this->loadResponseMocks('controller_calendarsources_sync_pagination.yml');
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

    public function testSyncUpdateSyncToken(): void
    {
        $this->loadResponseMocks('controller_calendarsources_sync.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->calendar->syncEvents($source);
        $source = $this->calendarSources->get($source->id);
        $this->assertSame('next-sync-token', $source->sync_token);
    }

    public function testSyncUpdateRecurringInstances(): void
    {
        $this->loadResponseMocks('calendarservice_instances.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->calendar->syncEvents($source);
        $query = $this->calendarItems->find()
            ->where(['CalendarItems.calendar_source_id' => $source->id])
            ->orderByAsc('CalendarItems.provider_id');
        $results = $query->all();
        $this->assertCount(2, $results);
        $this->assertEquals(['calendar-event-3', 'calendar-event-4'], $results->extract('provider_id')->toList());
    }

    public function testListUnsyncedCalendars(): void
    {
        $this->loadResponseMocks('controller_calendarsources_add.yml');
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

    public function testGetSourceForSubscriptionInvalid(): void
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->expectException(RecordNotFoundException::class);
        $this->calendar->getSourceForSubscription('nope', 'also nope');
    }

    public function testGetSourceForSubscriptionInvalidVerifier(): void
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $sub = $this->makeCalendarSubscription($source->id);
        $this->expectException(RecordNotFoundException::class);
        $this->calendar->getSourceForSubscription($sub->identifier, 'nope');
    }

    public function testCreateSubscriptionSuccess(): void
    {
        $this->loadResponseMocks('calendarservice_createsubscription_success.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $sub = $this->calendar->createSubscription($source);
        $this->assertNotEmpty($sub);
        $this->assertSame($source->id, $sub->calendar_source_id);
        $this->assertNotEmpty($sub->identifier);
        $this->assertNotEmpty($sub->resource_id);
        $this->assertNotEmpty($sub->verifier);
        $this->assertNotEmpty($sub->expires_at);
    }

    public function testCreateSubscriptionFailure(): void
    {
        $this->loadResponseMocks('calendarservice_createsubscription_failure.yml');
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);
        $this->expectException(RuntimeException::class);
        $this->calendar->createSubscription($source);
    }
}
