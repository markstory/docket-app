<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Facebook\WebDriver\WebDriverKeys;
use Symfony\Component\Panther\Client;

class TasksTest extends AcceptanceTestCase
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

    protected function setupTask()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);

        return $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);
    }

    protected function openDueOnMenu(Client $client)
    {
        $crawler = $client->getCrawler();
        $crawler->filter('due-on .button-secondary')->click();
        $client->waitFor('due-on drop-down-menu');
    }

    public function testViewMarkComplete()
    {
        $task = $this->setupTask();

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $title = $crawler->filter('.task-title-input')->first();
        $this->assertEquals($task->title, $title->attr('value'));
        $checkbox = $crawler->filter('.task-view-summary .checkbox')->first();
        $checkbox->click();

        $button = $crawler->filter('[data-testid="save-task"]')->first();
        $button->click();

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

        // Open the date menu, clear the due date
        $this->openDueOnMenu($client);
        $this->clickWithMouse('due-on [data-testid="later"]');

        // Submit the form
        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertNull($task->due_on);
    }

    public function testViewUpdateTitleAndBody()
    {
        $tomorrow = new FrozenDate('tomorrow', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Fill out the form and submit it.

        $form = $crawler->filter('.task-view form')->form();
        $form->get('title')->setValue('Cut grass');

        // Show textarea for notes
        $crawler->filter('.markdown-text-preview .button-muted')->click();
        $form->get('body')->setValue('Make the grass shorter');

        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertEquals('Cut grass', $task->title);
        $this->assertEquals('Make the grass shorter', $task->body);
    }

    public function testViewUpdateDueOnMention()
    {
        $this->markTestIncomplete('mentions are not working yet');

        $project = $this->makeProject('Work', 1);
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

    public function testViewUpdateProjectMention()
    {
        $this->markTestIncomplete('mentions are not working yet');

        $project = $this->makeProject('Home', 1);
        $work = $this->makeProject('Work', 1);
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
        $title->sendKeys('#Work');
        $title->sendKeys(WebDriverKeys::ENTER);

        $crawler->filter('[data-testid="save-task"]')->click();

        $task = $this->Tasks->get($task->id);
        $this->assertNotEmpty($task);
        $this->assertEquals('Do dishes', $task->title);
        $this->assertSame($work->id, $task->project_id);
    }

    public function testViewCreateSubtask()
    {
        $task = $this->setupTask();

        $client = $this->login();
        $client->get("/tasks/{$task->id}/view");
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Fill out the subtask form and submit it.
        $client->waitFor('.subtask-addform');
        $form = $crawler->filter('.task-view form')->form();
        $form->get('_subtaskadd')->setValue('Get soap');
        $crawler->filter('[data-testid="subtask-add"]')->click();
        $client->waitFor('.subtask-item');

        // Save task
        $crawler->filter('[data-testid="save-task"]')->click();

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
        $crawler->filter('.subtask-item .checkbox')->click();

        // Save task
        $crawler->filter('[data-testid="save-task"]')->click();

        $subtask = $this->Tasks->Subtasks->get($subtask->id);
        $this->assertTrue($subtask->completed);
    }

    public function testViewReorderSubtasks()
    {
        $this->markTestIncomplete('Cannot test HTML5 drag and drop with selenium');

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
