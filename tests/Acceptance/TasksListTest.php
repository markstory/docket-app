<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * Tests for the today and upcoming lists.
 */
class TasksListTest extends AcceptanceTestCase
{
    /**
     * @var \App\Model\Table\TasksTable
     */
    protected $Tasks;

    public function setUp(): void
    {
        parent::setUp();

        $this->Tasks = TableRegistry::get('Tasks');
    }

    public function testTodayOnboarding()
    {
        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        $crawler = $client->getCrawler();

        $button = $crawler->filter('.no-projects a')->first();
        $button->click();
        $client->waitFor('[data-testid="loggedin"]');
        $this->assertStringContainsString('/projects/add', $client->getCurrentURL());
    }

    public function testCreateFromToday()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the add form
        $button = $crawler->filter('[data-testid="add-task"]');
        $button->click();
        $client->waitFor('.task-quickform');

        $title = $crawler->filter('.task-quickform .smart-task-input input');
        $title->sendKeys('A new task');

        // Use the default project value as it is hard to automate with webdriver.
        // Consider https://stackoverflow.com/questions/41991077/testing-react-select-component
        // when needing to automate that component comes up again.
        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Tasks->find()->first();
        $this->assertNotEmpty($task, 'No task saved');
        $this->assertEquals('A new task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
    }

    public function testCreateInEvening()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the add form in the evening section.
        $button = $crawler->filter('[data-testid="evening-group"] [data-testid="add-task"]');
        $button->click();
        $client->waitFor('.task-quickform');

        $title = $crawler->filter('.task-quickform .smart-task-input input');
        $title->sendKeys('evening task');

        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Tasks->find()->firstOrFail();
        $this->assertEquals('evening task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertTrue($task->evening);
    }

    public function testUpcomingOnboarding()
    {
        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        $crawler = $client->getCrawler();

        $button = $crawler->filter('.no-projects a')->first();
        $button->click();
        $client->waitFor('[data-testid="loggedin"]');
        $this->assertStringContainsString('/projects/add', $client->getCurrentURL());
    }

    public function testCompleteOnUpcomingList()
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

    public function testChangeDateWithContextMenuOnUpcomingList()
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
        $client->waitFor('.task-row .actions');

        // Hover over the due menu
        $crawler = $client->getCrawler();
        $client->getMouse()->mouseMoveTo('.actions [aria-label="Task actions"]');
        $crawler->filter('.actions [aria-label="Task actions"]')->click();

        // Open the due menu
        $crawler->filter('[data-testid="reschedule"]')->click();
        $client->waitFor('.due-on-menu');
        // Click today
        $this->clickWithMouse('.due-on-menu [data-testid="today"]');

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
        $client->waitFor('.task-row .actions');

        // Hover over the actions menu and click
        $crawler = $client->getCrawler();
        $client->getMouse()->mouseMoveTo('.actions [aria-label="Task actions"]');
        $crawler->filter('.actions [aria-label="Task actions"]')->click();

        // Open the due menu
        $crawler->filter('[data-testid="reschedule"]')->click();
        $client->waitFor('.due-on-menu');

        // Click 'this evening'
        $this->clickWithMouse('.due-on-menu [data-testid="evening"]');

        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertLessThan($tomorrow->getTimestamp(), $updated->due_on->getTimestamp());
        $this->assertTrue($updated->evening);
    }

    public function testChangeProjectWithContextMenuOnUpcomingList()
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
        $client->waitFor('.task-row .actions');

        // Hover over the actions menu
        $crawler = $client->getCrawler();
        $client->getMouse()->mouseMoveTo('.actions [aria-label="Task actions"]');
        $crawler->filter('.actions [aria-label="Task actions"]')->click();

        // Open the project menu
        $crawler->filter('[data-testid="move"]')->click();
        $client->waitFor('.select__control');

        // Choose a new project.
        $this->clickWithMouse('.select__control');
        // TODO Using last-child is a hack. Revisit this and make better selectors.
        $this->clickWithMouse('.select__menu-list > div:last-child');

        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertEquals($zoo->id, $updated->project_id);
    }

    public function testReorderInToday()
    {
        $this->markTestSkipped('This fails in github actions, but passes locally.');

        $date = new FrozenDate('yesterday', 'UTC');
        $project = $this->makeProject('Work', 1);

        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $date]);
        $this->makeTask('Vacuum', $project->id, 1, ['due_on' => $date]);
        $this->makeTask('Take out trash', $project->id, 2, ['due_on' => $date]);
        $this->makeTask('Clean Bathtub', $project->id, 3, ['due_on' => $date]);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        $middle = $client->getCrawler()->filter('.task-group .dnd-handle')->getElement(2);

        $mouse = $client->getMouse();
        // Do a drag from the top to the bottom
        $mouse->mouseDownTo('.task-group .dnd-item:first-child .dnd-handle')
            ->mouseMove($middle->getCoordinates(), 0, 20)
            ->mouseUp($middle->getCoordinates(), 0, 20);

        $client->waitFor('.flash-message');

        $task = $this->Tasks->get($task->id);
        $this->assertGreaterThan(0, $task->day_order);
    }

    public function testDragToNewDateOnUpcomingList()
    {
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
