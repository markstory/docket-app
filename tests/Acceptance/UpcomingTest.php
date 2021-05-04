<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

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

        $this->Tasks = TableRegistry::get('Tasks');
    }

    public function testOnboarding()
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
        $client->waitFor('.task-quickform');

        $title = $crawler->filter('.task-quickform .smart-task-input input');
        $title->sendKeys('upcoming task');

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

    public function testDragToNewDate()
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
