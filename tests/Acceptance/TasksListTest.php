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

    public function testCreateFromToday()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        // Open the add form
        $button = $client->getCrawler()->filter('.add-task button');
        $button->click();
        $client->waitFor('.task-quickform');

        $form = $client->getCrawler()->filter('.task-quickform')->form();
        $form->setValues(['title' => 'A new task']);

        // Use the default project value as it is hard to automate with webdriver.
        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Tasks->find()->first();
        $this->assertNotEmpty($task, 'No task saved');
        $this->assertEquals('A new task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
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
        $checkbox = $crawler->filter('.task-row input[type="checkbox"]')->first();
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

        // Open the due menu
        $crawler->filter('.actions [data-testid="task-reschedule"]')->click();

        // click today
        $crawler->filter('.due-on-menu [data-testid="today"]')->click();
        $client->waitFor('.flash-message');

        $updated = $this->Tasks->get($task->id);
        $this->assertLessThan($tomorrow->getTimestamp(), $updated->due_on->getTimestamp());
    }

    public function testReorderInToday()
    {
        $yesterday = new FrozenDate('yesterday', 'UTC');
        $project = $this->makeProject('Work', 1);

        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $yesterday]);
        $this->makeTask('Vacuum', $project->id, 1, ['due_on' => $yesterday]);
        $this->makeTask('Take out trash', $project->id, 2, ['due_on' => $yesterday]);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        $last = $client->getCrawler()->filter('.task-group .dnd-handle')->getElement(2);

        // Do a drag from the top to the bottom
        $mouse = $client->getMouse();
        $mouse->mouseDownTo('.task-group .dnd-item:first-child .dnd-handle')
            ->mouseMove($last->getCoordinates())
            ->mouseUp($last->getCoordinates());

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
        $this->assertEquals(0, $updated->day_order);

        $updated = $this->Tasks->get($other->id);
        $this->assertEquals($twoDays, $updated->due_on);
        $this->assertEquals(1, $updated->day_order);
    }
}
