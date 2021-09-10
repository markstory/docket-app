<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\CalendarSubscriptionRenewCommand Test Case
 *
 * @uses \App\Command\CalendarSubscriptionRenewCommand
 */
class CalendarSubscriptionRenewCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use FactoryTrait;

    public $fixtures = [
        'app.Users',
        'app.CalendarProviders',
        'app.CalendarSources',
        'app.CalendarSubscriptions',
    ];

    /**
     * @var \App\Model\Table\CalendarSubscriptionsTable
     */
    private $CalendarSubscriptions;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
        $this->CalendarSubscriptions = TableRegistry::get('CalendarSubscriptions');
    }

    /**
     * Test execute method
     *
     * @vcr calendarservice_createsubscription_success.yml
     * @return void
     */
    public function testExecute(): void
    {
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
     * Test execute method
     *
     * @vcr calendarservice_createsubscription_success.yml
     * @return void
     */
    public function testExecuteCreateMissing(): void
    {
        $provider = $this->makeCalendarProvider(1, 'me@example.com');
        $this->makeCalendarSource($provider->id, 'calendar-1');
        $this->exec('calendar_subscription_renew');

        $this->assertExitSuccess();
        $this->assertErrorEmpty();
        $this->assertOutputContains('Calendar subscription created.');

        $subs = $this->CalendarSubscriptions->find()->all();
        $this->assertCount(1, $subs, 'Should create a new subscription.');
    }
}
