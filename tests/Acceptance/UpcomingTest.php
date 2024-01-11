<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;
use Symfony\Component\Panther\Client;

/**
 * Tests for the upcoming list view.
 */
class UpcomingTest extends AcceptanceTestCase
{
    /**
     * @var \App\Model\Table\TasksTable
     */
    protected $Tasks;

    public function setUp(): void
    {
        parent::setUp();

        $this->Tasks = $this->fetchTable('Tasks');
    }

    public function hoverRow(Client $client)
    {
        // Trigger the hover.
        $client->getMouse()->mouseMoveTo('.task-row');
        $client->waitFor('.task-row drop-down');
    }

    public function testOnboarding()
    {
        $this->markTestIncomplete('No project state is not working');

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        $crawler = $client->getCrawler();

        $button = $crawler->filter('.no-projects a')->first();
        $button->click();
        $client->waitFor('[data-testid="loggedin"]');
        $this->assertStringContainsString('/projects/add', $client->getCurrentURL());
    }

    public function testUpcomingDisplaysCalendarEvents()
    {
        $twodays = FrozenDate::parse('+2 days', 'UTC');
        $project = $this->makeProject('Work', 1);
        $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $twodays]);

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);
        $event = $this->makeCalendarItem($source->id, [
            'title' => 'First Day of School',
            'provider_id' => 'event-1',
            'start_time' => $twodays->format('Y-m-d H:i:s'),
            'end_time' => $twodays->format('Y-m-d H:i:s'),
        ]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Has an event list and the event text present.
        $events = $crawler->filter('.calendar-item-list')->first();
        $this->assertStringContainsString($event->title, $events->getText());
    }

    public function testCompleteTask()
    {
        $today = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $title = $crawler->filter('.task-row .title')->first();
        $this->assertEquals($task->title, $title->getText());
        $checkbox = $crawler->filter('.task-row .checkbox')->first();
        $checkbox->click();
        $client->waitFor('.flash-message');

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertTrue($task->completed);
    }

    public function testCreate()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        $crawler = $client->getCrawler();

        // Open the add form.
        $addButton = $crawler->filter('[data-testid="add-task"]')->first();
        $addButton->click();
        $client->waitFor('.sheet-body');

        $form = $crawler->filter('.sheet-body form')->form();
        $form->get('title')->setValue('upcoming task');

        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Tasks->find()->firstOrFail();
        $this->assertEquals('upcoming task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertFalse($task->evening);
    }

    public function testChangeDateWithContextMenu()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Trigger the hover.
        $client->getMouse()->mouseMoveTo('.task-row');
        $crawler = $client->getCrawler();
        $client->waitFor('.task-row drop-down');

        // Hover over the due menu
        $client->getMouse()->mouseMoveTo('drop-down [aria-label="Task actions"]');
        $crawler->filter('drop-down [aria-label="Task actions"]')->click();

        // Open the due menu
        $crawler->filter('[data-testid="reschedule"]')->click();

        // Click today
        $client->waitFor('[data-testid="today"]');
        $this->clickWithMouse('[data-testid="today"]');

        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertLessThan($tomorrow->getTimestamp(), $updated->due_on->getTimestamp());
    }

    public function testChangeDateAndEveningWithContextMenu()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Trigger the hover.
        $client->getMouse()->mouseMoveTo('.task-row');
        $crawler = $client->getCrawler();
        $client->waitFor('.task-row drop-down');

        // Hover over the actions menu and click
        $crawler = $client->getCrawler();
        $client->getMouse()->mouseMoveTo('drop-down [aria-label="Task actions"]');
        $crawler->filter('drop-down [aria-label="Task actions"]')->click();

        // Open the due menu
        $crawler->filter('[data-testid="reschedule"]')->click();

        // Click 'this evening'
        $client->waitFor('drop-down-menu button.icon-evening');
        $this->clickWithMouse('drop-down-menu button.icon-evening');

        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertLessThan($tomorrow->getTimestamp(), $updated->due_on->getTimestamp());
        $this->assertTrue($updated->evening);
    }

    public function testChangeAddEveningWithContextMenu()
    {
        $today = new FrozenDate('today', 'UTC');
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        $this->hoverRow($client);

        // Hover over the actions menu and click
        $crawler = $client->getCrawler();
        $client->getMouse()->mouseMoveTo('drop-down [aria-label="Task actions"]');
        $crawler->filter('drop-down [aria-label="Task actions"]')->click();

        // Open the due menu
        $crawler->filter('[data-testid="reschedule"]')->click();

        // Click 'this evening'
        $client->waitFor('drop-down-menu [data-testid="evening"]');
        $this->clickWithMouse('drop-down-menu [data-testid="evening"]');

        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertTrue($updated->evening);
        $this->assertSame($today->getTimestamp(), $updated->due_on->getTimestamp());
    }

    public function testChangeProjectWithContextMenu()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $zoo = $this->makeProject('Zoo', 1);
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Trigger the hover.
        $client->getMouse()->mouseMoveTo('.task-row');
        $crawler = $client->getCrawler();
        $client->waitFor('.task-row drop-down');

        // Hover over the actions menu
        $crawler = $client->getCrawler();
        $client->getMouse()->mouseMoveTo('drop-down [aria-label="Task actions"]');
        $crawler->filter('drop-down [aria-label="Task actions"]')->click();

        // Open the project menu
        $crawler->filter('drop-down-menu [data-testid="move"]')->click();
        $client->waitFor('drop-down-menu form');

        // Choose a new project.
        $crawler->filter('drop-down-menu select-box')->click();
        $client->waitFor('select-box-menu');

        $crawler->filter('select-box-menu select-box-option:last-child')->click();

        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertEquals($zoo->id, $updated->project_id);
    }

    public function testDragToNewDate()
    {
        $this->markTestIncomplete('Cannot test html5 dragdrop with selenium.');

        $project = $this->makeProject('Work', 1);
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $twoDays = new FrozenDate('+2 days', 'UTC');

        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);
        $other = $this->makeTask('Vacuum', $project->id, 0, ['due_on' => $twoDays]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Get the last item. It will be in a different group than the first.
        $last = $client->getCrawler()->filter('.task-group .dnd-handle')->getElement(1);

        // Do a drag from the current day to the other day.
        $mouse = $client->getMouse();
        $mouse->mouseDownTo('.task-group .dnd-item:first-child .dnd-handle')
            ->mouseMove($last->getCoordinates())
            ->mouseUp($last->getCoordinates());
        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertEquals($twoDays, $updated->due_on);
        $this->assertEquals(1, $updated->day_order);

        $updated = $this->Tasks->get($other->id);
        $this->assertEquals($twoDays, $updated->due_on);
        $this->assertEquals(0, $updated->day_order);
    }
}
