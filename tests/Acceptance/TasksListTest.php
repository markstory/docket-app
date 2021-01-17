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
        $this->markTestIncomplete();
    }

    public function testDragToNewDateOnUpcomingList()
    {
        $this->markTestIncomplete();
    }
}
