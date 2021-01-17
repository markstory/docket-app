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
        $this->markTestIncomplete();
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
        // TODO operate react select with webdriver.
        // $client->getCrawler()->filter('...)

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

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertTrue($task->completed);
    }

    public function testChangeDateWithContextMenuOnUpcomingList()
    {
        $today = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);
        $this->markTestIncomplete();
    }

    public function testReorderInToday()
    {
        $today = new FrozenDate('yesterday', 'UTC');
        $project = $this->makeProject('Work', 1);

        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);
        $this->makeTask('Vacuum', $project->id, 1, ['due_on' => $today]);
        $this->makeTask('Take out trash', $project->id, 2, ['due_on' => $today]);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        $last = $client->getCrawler()->filter('.task-group .dnd-handle')->getElement(2);

        // Do a drag from the top to the bottom subtask
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
        $today = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);

        $this->markTestIncomplete();
    }
}
