<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

class TasksTest extends AcceptanceTestCase
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

    public function testCompleteOnView()
    {
        $today = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $title = $crawler->filter('.summary h3')->first();
        $this->assertEquals($task->title, $title->getText());
        $checkbox = $crawler->filter('.summary input[type="checkbox"]')->first();
        $checkbox->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertTrue($task->completed);
    }

    public function testRenameOnView()
    {
        $today = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click the summary to get the form.
        $summary = $crawler->filter('.summary > a')->first();
        $summary->click();
        $client->waitFor('.task-quickform');

        // Fill out the form and submit it.
        $form = $crawler->filter('.task-quickform')->form();
        $form->get('title')->setValue('Cut grass');
        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertEquals('Cut grass', $task->title);
    }

    public function testTaskCreateFromToday()
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
}
