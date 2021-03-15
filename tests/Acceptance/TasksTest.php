<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Facebook\WebDriver\WebDriverKeys;

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

    protected function setupTask()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);

        return $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);
    }

    public function testViewMarkComplete()
    {
        $task = $this->setupTask();

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $title = $crawler->filter('.task-view-summary h3')->first();
        $this->assertEquals($task->title, $title->getText());
        $checkbox = $crawler->filter('.task-view-summary input[type="checkbox"]')->first();
        $checkbox->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertTrue($task->completed);
    }

    public function testViewUpdateDueOn()
    {
        $task = $this->setupTask();

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click the title to get the form.
        $summary = $crawler->filter('.task-view-summary h3')->first();
        $summary->click();
        $client->waitFor('.task-quickform');

        // Open the date menu, clear the due date
        $crawler->filter('[data-testid="due-on"]')->click();
        $client->waitFor('.due-on-menu');
        $this->clickWithMouse('.due-on-menu [data-testid="not-due"]');

        // Submit the form
        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertNull($task->due_on);
    }

    public function testViewUpdateName()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click the summary to get the form.
        $summary = $crawler->filter('.task-view-summary h3')->first();
        $summary->click();
        $client->waitFor('.task-quickform');

        // Fill out the form and submit it.
        $title = $crawler->filter('.task-quickform .smart-task-input input');
        $title->sendKeys([WebDriverKeys::CONTROL, 'a']);
        $title->sendKeys('Cut grass');

        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertEquals('Cut grass', $task->title);
    }

    public function testViewProjectMention()
    {
        $project = $this->makeProject('Work', 1);
        $home = $this->makeProject('Home', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0);

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click the summary to get the form.
        $summary = $crawler->filter('.task-view-summary h3')->first();
        $summary->click();
        $client->waitFor('.task-quickform');

        // Fill out the form and submit it.
        $title = $crawler->filter('.task-quickform .smart-task-input input');
        $title->sendKeys([WebDriverKeys::CONTROL, 'a']);
        $title->sendKeys('Do dishes ');
        $title->sendKeys('%Tomorrow');
        $title->sendKeys(WebDriverKeys::ENTER);

        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertEquals('Do dishes', $task->title);
        $this->assertNotNull($task->due_on);
    }

    public function testViewCreateSubtask()
    {
        $task = $this->setupTask();

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click the add subtask button
        $crawler->filter('.add-subtask button')->click();

        // Fill out the form and submit it.
        $client->waitFor('.subtask-addform');
        $form = $crawler->filter('.subtask-addform')->form();
        $form->get('title')->setValue('Get soap');
        $crawler->filter('[data-testid="save-subtask"]')->click();

        $subtask = $this->Tasks->Subtasks
            ->find()
            ->where(['Subtasks.task_id' => $task->id])
            ->firstOrFail();

        $this->assertNotEmpty($subtask);
        $this->assertEquals('Get soap', $subtask->title);
    }

    public function testViewMarkSubtaskComplete()
    {
        $task = $this->setupTask();
        $subtask = $this->makeSubtask('Get soap', $task->id, 0);

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Complete the subtask
        $crawler->filter('.subtask-row input[type="checkbox"]')->click();

        $subtask = $this->Tasks->Subtasks->get($subtask->id);
        $this->assertTrue($subtask->completed);
    }

    public function testViewReorderSubtasks()
    {
        $task = $this->setupTask();
        $subtasks = [
            $this->makeSubtask('Get soap', $task->id, 0),
            $this->makeSubtask('Get brush', $task->id, 1),
            $this->makeSubtask('Fill sink', $task->id, 2),
        ];
        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');

        // Do a drag from the top to the bottom subtask
        $mouse = $client->getMouse();
        $mouse->mouseDownTo('.task-subtasks li:first-child .dnd-handle')
            ->mouseMoveTo('.task-subtasks li:last-child')
            ->mouseUpTo('.task-subtasks li:last-child');
        $client->waitFor('.flash-message');

        $subtask = $this->Tasks->Subtasks->get($subtasks[0]->id);
        $this->assertGreaterThan(0, $subtask->ranking);
    }
}
