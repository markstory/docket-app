<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\FrozenDate;

/**
 * Tests for the today view
 */
class TodayTest extends AcceptanceTestCase
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

    public function testTodayOnboarding()
    {
        $this->markTestIncomplete('Onboarding view not complete');

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        $crawler = $client->getCrawler();

        $button = $crawler->filter('.no-projects a')->first();
        $button->click();
        $client->waitFor('[data-testid="loggedin"]');
        $this->assertStringContainsString('/projects/add', $client->getCurrentURL());
    }

    public function testTodayDisplaysCalendarEvents()
    {
        $today = new FrozenDate('today', 'UTC');
        $project = $this->makeProject('Work', 1);
        $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);
        $event = $this->makeCalendarItem($source->id, [
            'title' => 'First Day of School',
            'provider_id' => 'event-1',
            'start_time' => $today->format('Y-m-d H:i:s'),
            'end_time' => $today->format('Y-m-d H:i:s'),
        ]);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Has an event list and the event text present.
        $events = $crawler->filter('.calendar-item-list')->first();
        $this->assertStringContainsString($event->title, $events->getText());
    }

    public function testCreateInToday()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the add form
        $button = $crawler->filter('[data-testid="add-task"]');
        $button->click();
        $client->waitFor('dialog');

        $form = $crawler->filter('dialog form')->form();
        $form->get('title')->setValue('A new task');

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
        $button = $crawler->filter('[data-testid="add-task-evening"]');
        $button->click();
        $client->waitFor('dialog');

        $form = $crawler->filter('dialog form')->form();
        $form->get('title')->setValue('evening task');

        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Tasks->find()->firstOrFail();
        $this->assertEquals('evening task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertTrue($task->evening);
    }

    public function testCreateWithSubtasks()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the add form
        $button = $crawler->filter('[data-testid="add-task"]');
        $button->click();
        $client->waitFor('dialog');

        // Fill out the task title
        $form = $crawler->filter('dialog form')->form();
        $form->get('title')->setValue('A new task');

        // Add a subtask
        $form->get('_subtaskadd')->setValue('First subtask');
        $button = $crawler->filter('[data-testid="subtask-add"]');
        $button->click();

        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Tasks
            ->find()
            ->contain('Subtasks')
            ->firstOrFail();
        $this->assertNotEmpty($task, 'No task saved');
        $this->assertEquals('A new task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertCount(1, $task->subtasks);
        $this->assertEquals('First subtask', $task->subtasks[0]->title);
    }

    public function testCompleteTask()
    {
        $today = new FrozenDate('today', 'UTC');
        $project = $this->makeProject('Work', 1);
        $task = $this->makeTask('Do dishes', $project->id, 0, ['due_on' => $today]);

        $client = $this->login();
        $client->get('/tasks/today');
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
}
