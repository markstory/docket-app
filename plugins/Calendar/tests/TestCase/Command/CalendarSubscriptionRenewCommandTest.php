<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Command;

use App\Test\TestCase\FactoryTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Calendar\Model\Table\CalendarSubscriptionsTable;

/**
 * App\Command\CalendarSubscriptionRenewCommand Test Case
 */
class CalendarSubscriptionRenewCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use FactoryTrait;

    public array $fixtures = [
        'app.Users',
        'plugin.Calendar.CalendarProviders',
        'plugin.Calendar.CalendarSources',
        'plugin.Calendar.CalendarSubscriptions',
    ];

    /**
     * @var \Calendar\Model\Table\CalendarSubscriptionsTable
     */
    private CalendarSubscriptionsTable $CalendarSubscriptions;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->CalendarSubscriptions = $this->fetchTable('Calendar.CalendarSubscriptions');
    }

    /**
     * Test execute method
     */
    public function testExecute(): void
    {
        $this->loadResponseMocks('calendarservice_createsubscription_success.yml');
        $provider = $this->makeCalendarProvider(1, 'me@example.com');
        $source = $this->makeCalendarSource($provider->id, 'calendar-1');
        $sub = $this->makeCalendarSubscription($source->id, 'abc-123', 'abc-456', strtotime('-6 hours'));
        $this->exec('calendar_subscription_renew');

        $this->assertExitSuccess();
        $this->assertErrorEmpty();
        $this->assertOutputContains('Calendar subscription created.');
        $removed = $this->CalendarSubscriptions->find()
            ->where(['id' => $sub->id])
            ->first();
        $this->assertEmpty($removed);

        $subs = $this->CalendarSubscriptions->find()->all();
        $this->assertCount(1, $subs, 'Should create a new subscription.');
    }

    /**
     * Test execute method create missing subscriptions
     */
    public function testExecuteCreateMissing(): void
    {
        $this->loadResponseMocks('calendarservice_createsubscription_success.yml');
        $provider = $this->makeCalendarProvider(1, 'me@example.com');
        $this->makeCalendarSource($provider->id, 'calendar-1');
        $this->exec('calendar_subscription_renew');

        $this->assertExitSuccess();
        $this->assertErrorEmpty();
        $this->assertOutputContains('Calendar subscription created.');

        $subs = $this->CalendarSubscriptions->find()->all();
        $this->assertCount(1, $subs, 'Should create a new subscription.');
    }

    /**
     * Test execute method will not create duplicates
     */
    public function testExecuteNoDuplicates(): void
    {
        $this->loadResponseMocks('calendarservice_createsubscription_success.yml');
        $provider = $this->makeCalendarProvider(1, 'me@example.com');
        $source = $this->makeCalendarSource($provider->id, 'calendar-1');
        $this->makeCalendarSubscription($source->id, 'abc123', 'verifier-val');

        $this->exec('calendar_subscription_renew');

        $this->assertExitSuccess();
        $this->assertErrorEmpty();
        $this->assertOutputNotContains('Calendar subscription created.');

        $subs = $this->CalendarSubscriptions->find()->all();
        $this->assertCount(1, $subs, 'Should create a new subscription.');
    }
}
