<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Test to ensure that API tokens work well as login credentials.
 */
class ApiTokenAuthenticationTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Tasks.Tasks',
        'plugin.Tasks.Projects',
        'plugin.Tasks.ProjectSections',
        'plugin.Tasks.Subtasks',
        'app.LabelsTasks',
        'app.Labels',
        'plugin.Calendar.CalendarProviders',
        'plugin.Calendar.CalendarSources',
        'plugin.Calendar.CalendarItems',
        'app.ApiTokens',
    ];

    protected $project;

    protected $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->project = $this->makeProject('work', 1);
        $this->task = $this->makeTask('first', $this->project->id, 0, ['due_on' => DateTime::parse('tomorrow')]);

        $this->loginApi(1);
    }

    public function testTaskViewInvalidToken(): void
    {
        $this->useApiToken('invalid');
        $this->get('/api/tasks/upcoming');
        $this->assertResponseCode(401);
    }

    public function testTaskIndex(): void
    {
        $this->get('/api/tasks/upcoming');
        $this->assertResponseOk();
        $var = $this->viewVariable('tasks');
        $this->assertCount(1, $var);
        $this->assertSame($var->toArray()[0]->title, $this->task->title);
    }

    public function testTaskView(): void
    {
        $this->get("/api/tasks/{$this->task->id}/view");
        $this->assertResponseOk();
        $var = $this->viewVariable('task');
        $this->assertSame($var->title, $this->task->title);
    }

    public function testTaskUpdate(): void
    {
        $this->post("/api/tasks/{$this->task->id}/complete");
        $this->assertResponseOk();
        $task = $this->fetchTable('Tasks')->get($this->task->id);
        $this->assertTrue($task->completed);
    }
}
