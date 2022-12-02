<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TasksController Test Case
 *
 * @uses \App\Controller\TasksController
 */
class TasksControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * @var \App\Model\Table\TasksTable
     */
    protected $Tasks;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Users',
        'app.Tasks',
        'app.Projects',
        'app.ProjectSections',
        'app.Subtasks',
        'app.LabelsTasks',
        'app.Labels',
        'app.CalendarProviders',
        'app.CalendarSources',
        'app.CalendarItems',
    ];

    protected function dayOrderedTasks(array $conditions = [])
    {
        return $this->Tasks
            ->find()
            ->where($conditions)
            ->orderAsc('evening')
            ->orderAsc('day_order')
            ->toArray();
    }

    protected function childOrderedTasks(array $conditions = [])
    {
        return $this->Tasks
            ->find()
            ->where($conditions)
            ->orderAsc('child_order')
            ->toArray();
    }

    protected function assertOrder($expected, $results)
    {
        $this->assertCount(count($expected), $results, 'Number of tasks is wrong');
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id, "Incorrect task as index={$i}");
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->Tasks = TableRegistry::get('Tasks');
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $tomorrow = new FrozenDate('tomorrow');
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $second = $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow,
        ]);

        $this->login();
        $this->get('/tasks');

        $this->assertResponseOk();
        $this->assertSame('upcoming', $this->viewVariable('view'));

        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(2, $items);
        $ids = array_map(function ($i) {

            return $i->id;
        }, $items);
        $this->assertEquals([$first->id, $second->id], $ids);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndexApi(): void
    {
        $tomorrow = new FrozenDate('tomorrow');
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $second = $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow,
        ]);
        $token = $this->makeApiToken(1);

        $this->requestJson();
        $this->useApiToken($token->token);
        $this->get('/tasks');

        $this->assertResponseOk();
        $response = json_decode(strval($this->_response->getBody()), true);

        $this->assertArrayHasKey('tasks', $response);
        $this->assertArrayHasKey('calendarItems', $response);

        $this->assertCount(2, $response['tasks']);
        $ids = array_map(function ($i) {

            return $i['id'];
        }, $response['tasks']);
        $this->assertEquals([$first->id, $second->id], $ids);
    }

    public function testIndexCalendarItems(): void
    {
        $tomorrow = new FrozenDate('tomorrow');

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary');
        $allDay = $this->makeCalendarItem($source->id, [
            'title' => 'Bob birthday',
            'provider_id' => 'event-1',
            'start_date' => $tomorrow,
            'end_date' => $tomorrow,
            'start_time' => null,
            'end_time' => null,
            'all_day' => true,
        ]);

        $tomorrow = new FrozenTime('tomorrow');
        $lunch = $this->makeCalendarItem($source->id, [
            'title' => 'Lunch',
            'provider_id' => 'event-2',
            'start_time' => $tomorrow->setTime(12, 0),
            'end_time' => $tomorrow->setTime(13, 0),
            'all_day' => false,
        ]);

        $this->login();
        $this->disableErrorHandlerMiddleware();
        $this->get('/tasks');

        $this->assertResponseOk();
        $this->assertSame('upcoming', $this->viewVariable('view'));

        $items = $this->viewVariable('calendarItems')->toArray();
        $this->assertCount(2, $items);
        $this->assertEquals($allDay->id, $items[0]->id);
        $this->assertEquals($lunch->id, $items[1]->id);
    }

    public function testIndexSetErrorsFromSession(): void
    {
        $this->session([
            'errors' => ['title' => 'Not valid'],
        ]);
        $tomorrow = new FrozenDate('tomorrow');
        $project = $this->makeProject('work', 1);
        $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);

        $this->login();
        $this->get('/tasks');

        $this->assertResponseOk();
        $errors = $this->viewVariable('errors');
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('title', $errors);
    }

    public function testIndexToday()
    {
        $today = new FrozenDate('today');
        $tomorrow = new FrozenDate('tomorrow');
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $today]);
        $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow,
        ]);

        $this->login();
        $this->get('/tasks/today');
        $this->assertResponseOk();
        $this->assertSame('today', $this->viewVariable('view'));

        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(1, $items);
        $ids = array_map(function ($i) {

            return $i->id;
        }, $items);
        $this->assertEquals([$first->id], $ids);
    }

    public function testIndexPermissions()
    {
        $tomorrow = new FrozenDate('tomorrow');
        $other = $this->makeProject('work', 2);
        $project = $this->makeProject('work', 1);

        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $this->makeTask('first', $other->id, 3, ['due_on' => $tomorrow]);

        $this->login();
        $this->get('/tasks/upcoming');
        $this->assertResponseOk();
        $this->assertSame('upcoming', $this->viewVariable('view'));

        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(1, $items);
        $ids = array_map(function ($i) {

            return $i->id;
        }, $items);
        $this->assertEquals([$first->id], $ids);
    }

    /**
     * Test index for deleted
     *
     * @return void
     */
    public function testIndexDeleted(): void
    {
        $tomorrow = new FrozenDate('tomorrow');
        $project = $this->makeProject('work', 1);
        $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $second = $this->makeTask('second', $project->id, 3, [
            'due_on' => $tomorrow,
            'deleted_at' => $tomorrow,
        ]);
        $third = $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow,
            'deleted_at' => $tomorrow,
        ]);

        $this->login();
        $this->get('/tasks/deleted');

        $this->assertResponseOk();
        $this->assertSame('deleted', $this->viewVariable('view'));

        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(2, $items);
        $ids = array_map(function ($i) {
            return $i->id;
        }, $items);
        $this->assertEquals([$second->id, $third->id], $ids);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->get("/tasks/{$first->id}/view");
        $this->assertResponseOk();
        $var = $this->viewVariable('task');
        $this->assertSame($var->title, $first->title);
    }

    public function testViewPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->get("/tasks/{$first->id}/view");
        $this->assertResponseCode(403);
    }

    public function testViewApi()
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->get("/tasks/{$first->id}/view");

        $this->assertResponseOk();
        $var = $this->viewVariable('task');
        $this->assertSame($var->title, $first->title);
    }

    public function testAdd(): void
    {
        $project = $this->makeProject('work', 1);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);

        $task = $this->viewVariable('task');
        $this->assertSame('first todo', $task->title);
        $this->assertNotEmpty($task->project);
        $this->assertSame('work', $task->project->slug);

        $project = $this->Tasks->Projects->get($project->id);
        $this->assertEquals(1, $project->incomplete_task_count);
    }

    public function testAddApiToken(): void
    {
        $project = $this->makeProject('work', 1);
        $token = $this->makeApiToken(1);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertResponseOk();

        $todo = $this->Tasks->find()->firstOrFail();
        $this->assertSame('first todo', $todo->title);
    }

    public function testAddToBottom(): void
    {
        $project = $this->makeProject('work', 1);
        $this->makeTask('existing', $project->id, 3, [
            'due_on' => '2020-12-17',
            'day_order' => 9,
        ]);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'project_id' => $project->id,
            'due_on' => '2020-12-17',
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);

        $todo = $this->Tasks->findByTitle('first todo')->firstOrFail();
        $this->assertSame(4, $todo->child_order);
        $this->assertSame(10, $todo->day_order);
    }

    public function testAddToSection(): void
    {
        $project = $this->makeProject('work', 1);
        $section = $this->makeProjectSection('release', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'section_id' => $section->id,
            'project_id' => $project->id,
            'due_on' => '2020-12-17',
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);

        $todo = $this->Tasks->findByTitle('first todo')->firstOrFail();
        $this->assertSame(1, $todo->child_order);
        $this->assertSame(1, $todo->day_order);
        $this->assertSame($section->id, $todo->section_id);
    }

    public function testAddToSectionInDifferentProject(): void
    {
        $home = $this->makeProject('home', 1);
        $project = $this->makeProject('work', 1);
        $section = $this->makeProjectSection('release', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'section_id' => $section->id,
            'project_id' => $home->id,
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);
        $this->assertSessionHasKey('errors');
    }

    public function testAddPermissions(): void
    {
        $project = $this->makeProject('work', 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertResponseCode(403);
    }

    public function testEdit(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'evening' => true,
        ]);
        $this->assertResponseCode(200);
        $this->assertFlashElement('flash/success');

        $updated = $this->viewVariable('task');
        $this->assertSame('updated', $updated->title);
        $this->assertTrue($updated->evening);
        // Need to have the project as well.
        $this->assertSame('work', $updated->project->slug);
    }

    public function testEditApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => 'updated',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('"updated"');

        $updated = $this->viewVariable('task');
        $this->assertSame('updated', $updated->title);
        // Need to have the project as well.
        $this->assertSame('work', $updated->project->slug);
    }

    public function testEditValidation(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->requestJson();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => '',
            'evening' => true,
        ]);
        $this->assertResponseCode(422);
        $this->assertResponseContains('errors');
    }

    public function testEditValidationApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => '',
            'evening' => true,
        ]);
        $this->assertResponseCode(422);
        $this->assertResponseContains('errors');
    }

    public function testEditPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => 'updated',
        ]);
        $this->assertResponseCode(403);
    }

    public function testEditProjectPermission(): void
    {
        $other = $this->makeProject('work', 2);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'project_id' => $other->id,
        ]);
        $this->assertResponseCode(403);

        $todo = $this->Tasks->get($first->id);
        $this->assertEquals($project->id, $todo->project_id);
    }

    public function testEditChangeProjectWithSection(): void
    {
        $project = $this->makeProject('work', 1);
        $other = $this->makeProject('home', 1);
        $section = $this->makeProjectSection('design', $project->id);
        $first = $this->makeTask('first', $project->id, 0, ['section_id' => $section->id]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'project_id' => $other->id,
        ]);
        $this->assertResponseCode(200);

        $todo = $this->Tasks->get($first->id);
        $this->assertSame('updated', $todo->title);
        $this->assertNull($todo->section_id, 'Should blank section because project is different.');
    }

    public function testDelete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/delete");

        $this->assertRedirect(['_name' => 'tasks:today']);

        $deleted = $this->Tasks->get($first->id, ['deleted' => true]);
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testDeleteCannotDeleteAgain(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => FrozenTime::now()]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/delete");
        $this->assertResponseCode(404);
    }

    public function testDeleteApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/delete");
        $this->assertResponseOk();

        $deleted = $this->Tasks->get($first->id, ['deleted' => true]);
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testDeletePermission(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/delete");

        $deleted = $this->Tasks->get($first->id);
        $this->assertNull($deleted->deleted_at);
    }

    public function testUndelete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => FrozenTime::now()]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/undelete");

        $this->assertRedirect(['_name' => 'tasks:today']);

        $deleted = $this->Tasks->get($first->id);
        $this->assertNull($deleted->deleted_at);
    }

    public function testUndeleteApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => FrozenTime::now()]);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/undelete");
        $this->assertResponseOk();

        $deleted = $this->Tasks->get($first->id);
        $this->assertNull($deleted->deleted_at);
    }

    public function testUndeletePermission(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => FrozenTime::now()]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/undelete");

        $deleted = $this->Tasks->get($first->id, ['deleted' => true]);
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testCompletePermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/complete");
        $this->assertResponseCode(403);

        $todo = $this->Tasks->get($first->id);
        $this->assertFalse($todo->completed);
    }

    public function testComplete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/complete");
        $this->assertResponseCode(302);

        $todo = $this->Tasks->get($first->id);
        $this->assertTrue($todo->completed);
    }

    public function testCompleteApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/complete");
        $this->assertResponseOk();

        $todo = $this->Tasks->get($first->id);
        $this->assertTrue($todo->completed);
    }

    public function testIncompletePermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0, ['completed' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/incomplete");
        $this->assertResponseCode(403);

        $todo = $this->Tasks->get($first->id);
        $this->assertTrue($todo->completed);
    }

    public function testIncomplete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['completed' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/incomplete");
        $this->assertResponseCode(302);

        $todo = $this->Tasks->get($first->id);
        $this->assertFalse($todo->completed);
    }

    public function testIncompleteApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/incomplete");
        $this->assertResponseOk();

        $todo = $this->Tasks->get($first->id);
        $this->assertFalse($todo->completed);
    }

    public function testMovePermissions()
    {
        $project = $this->makeProject('work', 2);
        $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$second->id}/move", [
            'day_order' => 0,
        ]);
        $this->assertResponseCode(403);
    }

    public function testMoveInvalidDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => 0,
            'due_on' => 'not a date',
        ]);

        $this->assertRedirect(['_name' => 'tasks:today']);
        $this->assertFlashElement('flash/error');
    }

    public function testMoveInvalidOrder()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => -1,
        ]);

        $this->assertRedirect(['_name' => 'tasks:today']);
        $this->assertFlashElement('flash/error');
    }

    public function testMoveApiToken(): void
    {
        $token = $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->makeTask('second', $project->id, 1);

        $this->useApiToken($token->token);
        $this->requestJson();
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => 1,
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('"first"');

        $todo = $this->Tasks->get($first->id);
        $this->assertEquals(1, $todo->day_order);
    }

    public function testMoveUpSameDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $third = $this->makeTask('third', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$third->id, $first->id, $second->id];
        $this->post("/tasks/{$third->id}/move", [
            'day_order' => 0,
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);
        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertNull($results[$i]->due_on);
        }
    }

    public function testMoveDownSameDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $third = $this->makeTask('third', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$second->id, $third->id, $first->id];
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => 2,
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);

        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
    }

    public function testMoveDownTooLarge()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $third = $this->makeTask('third', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$second->id, $third->id, $first->id];
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => 1000,
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);

        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
    }

    public function testMoveDownDuplicateOrder()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 2);
        $third = $this->makeTask('third', $project->id, 2);
        $fourth = $this->makeTask('z fourth', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$fourth->id}/move", [
            'day_order' => 1,
        ]);
        $this->assertRedirect(['_name' => 'tasks:today']);

        $results = $this->dayOrderedTasks();
        $expected = [$first->id, $fourth->id, $second->id, $third->id];
        $this->assertOrder($expected, $results);
    }

    public function testMoveDifferentDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => '2020-12-13']);
        $second = $this->makeTask('second', $project->id, 2, ['due_on' => '2020-12-13']);
        $third = $this->makeTask('third', $project->id, 0, ['due_on' => '2020-12-14']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$third->id}/move", [
            'day_order' => 1,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->dayOrderedTasks();
        $expected = [$first->id, $third->id, $second->id];
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
        }
    }

    public function testMoveDifferentDayMiddle()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => '2020-12-13']);
        $second = $this->makeTask('second', $project->id, 1, ['due_on' => '2020-12-13']);
        $third = $this->makeTask('third', $project->id, 2, ['due_on' => '2020-12-13']);

        $new = $this->makeTask('new', $project->id, 0, ['due_on' => '2020-12-20']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$new->id}/move", [
            'day_order' => 1,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->dayOrderedTasks();
        $expected = [$first->id, $new->id, $second->id, $third->id];
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
        }
    }

    public function testMoveDifferentDayTop()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => '2020-12-13']);
        $second = $this->makeTask('second', $project->id, 1, ['due_on' => '2020-12-13']);
        $third = $this->makeTask('third', $project->id, 2, ['due_on' => '2020-12-13']);

        $new = $this->makeTask('new', $project->id, 6, ['due_on' => '2020-12-20']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$new->id}/move", [
            'day_order' => 0,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->dayOrderedTasks();
        $expected = [$new->id, $first->id, $second->id, $third->id];
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
        }

        $expected = [0, 1, 2, 3];
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->day_order);
        }
    }

    public function testMoveDifferentDayBottom()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 3, ['due_on' => '2020-12-13']);
        $second = $this->makeTask('second', $project->id, 4, ['due_on' => '2020-12-13']);
        $third = $this->makeTask('third', $project->id, 6, ['due_on' => '2020-12-13']);

        $new = $this->makeTask('new', $project->id, 6, ['due_on' => '2020-12-20']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$new->id}/move", [
            'day_order' => 3,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->dayOrderedTasks();
        $expected = [$first->id, $second->id, $third->id, $new->id];
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
        }
    }

    public function testMoveDifferentDaySameOrder()
    {
        $project = $this->makeProject('work', 1);
        $home = $this->makeProject('home', 1);

        $first = $this->makeTask('first', $project->id, 0, ['due_on' => '2020-12-13']);
        $second = $this->makeTask('second', $project->id, 1, ['due_on' => '2020-12-13']);
        $third = $this->makeTask('third', $home->id, 1, ['due_on' => '2020-12-14']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$third->id}/move", [
            'day_order' => 1,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->dayOrderedTasks();
        $expected = [$first->id, $third->id, $second->id];
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
        }
    }

    public function testMoveIntoEvening()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['evening' => false]);
        $second = $this->makeTask('second', $project->id, 3, ['evening' => false]);
        $third = $this->makeTask('third', $project->id, 2, ['evening' => true]);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$second->id, $third->id, $first->id];
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => 1,
            'evening' => true,
        ]);
        $this->assertRedirect('/tasks/today');
        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertNull($results[$i]->due_on);
        }
    }

    public function testMoveIntoEveningInterleavedOrder()
    {
        $project = $this->makeProject('work', 1);

        $first = $this->makeTask('first', $project->id, 0, ['evening' => false]);
        $second = $this->makeTask('second', $project->id, 2, ['evening' => true]);
        $third = $this->makeTask('third', $project->id, 3, ['evening' => false]);
        $fourth = $this->makeTask('fourth', $project->id, 4, ['evening' => true]);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$third->id, $second->id, $first->id, $fourth->id];
        $this->post("/tasks/{$first->id}/move", [
            'day_order' => 1,
            'evening' => true,
        ]);
        $this->assertRedirect('/tasks/today');
        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertNull($results[$i]->due_on);
        }
    }

    public function testMoveIntoEveningSplitOrder()
    {
        $project = $this->makeProject('work', 1);

        $first = $this->makeTask('first', $project->id, 2, ['evening' => false]);
        $second = $this->makeTask('second', $project->id, 3, ['evening' => false]);
        $third = $this->makeTask('third', $project->id, 4, ['evening' => false]);
        $fourth = $this->makeTask('fourth', $project->id, 1, ['evening' => true]);
        $fifth = $this->makeTask('fifth', $project->id, 5, ['evening' => true]);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$first->id, $third->id, $second->id, $fourth->id, $fifth->id];
        $this->post("/tasks/{$third->id}/move", [
            'day_order' => 1,
        ]);
        $this->assertRedirect('/tasks/today');
        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertNull($results[$i]->due_on);
        }
    }

    public function testMoveOutOfEvening()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['evening' => false]);
        $second = $this->makeTask('second', $project->id, 3, ['evening' => false]);
        $third = $this->makeTask('third', $project->id, 2, ['evening' => true]);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$first->id, $third->id, $second->id];
        $this->post("/tasks/{$third->id}/move", [
            'day_order' => 1,
            'evening' => false,
        ]);
        $this->assertRedirect('/tasks/today');
        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
        foreach ($expected as $i => $id) {
            $this->assertFalse($results[$i]->evening);
        }
    }

    public function testMoveProjectUp()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 2);
        $third = $this->makeTask('third', $project->id, 3);
        $fourth = $this->makeTask('fourth', $project->id, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$fourth->id}/move", [
            'child_order' => 1,
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->childOrderedTasks();
        $expected = [$first->id, $fourth->id, $second->id, $third->id];
        $this->assertOrder($expected, $results);
    }

    public function testMoveProjectDown()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $third = $this->makeTask('third', $project->id, 2);
        $fourth = $this->makeTask('fourth', $project->id, 3);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/move", [
            'child_order' => 2,
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->childOrderedTasks();
        $expected = [$second->id, $third->id, $first->id, $fourth->id];
        $this->assertOrder($expected, $results);
    }

    public function testMoveProjectBottom()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 7);
        $second = $this->makeTask('second', $project->id, 9);
        $third = $this->makeTask('third', $project->id, 10);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/move", [
            'child_order' => 2,
            'section_id' => null,
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->childOrderedTasks();
        $expected = [$second->id, $third->id, $first->id];
        $this->assertOrder($expected, $results);
    }

    public static function invalidMoveProvider()
    {
        return [
            // Can't set child and day
            [[
                'child_order' => 1,
                'day_order' => 1,
            ]],
            // Can't use due_on with child.
            [[
                'child_order' => 1,
                'due_on' => '2020-03-10',
            ]],
            // Can't use day with section
            [[
                'day_order' => 1,
                'section_id' => 1,
            ]],
            // Can't have string section
            [[
                'child_order' => 1,
                'section_id' => 'no',
            ]],
        ];
    }

    /**
     * @dataProvider invalidMoveProvider
     */
    public function testMoveConflictingParameters($operation)
    {
        $project = $this->makeProject('work', 1);
        $this->makeProjectSection('first', $project->id);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/tasks/{$first->id}/move", $operation);
        $this->assertResponseCode(302);
        $this->assertFlashElement('flash/error');
    }

    public function testMoveToNewSection()
    {
        $project = $this->makeProject('work', 1);
        $design = $this->makeProjectSection('design', $project->id, 0);
        $build = $this->makeProjectSection('build', $project->id, 1);

        $first = $this->makeTask('first', $project->id, 0, ['section_id' => $design->id]);
        $this->makeTask('second', $project->id, 1, ['section_id' => $design->id]);
        $third = $this->makeTask('third', $project->id, 0, ['section_id' => $build->id]);
        $fourth = $this->makeTask('fourth', $project->id, 1, ['section_id' => $build->id]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/move", [
            'child_order' => 2,
            'section_id' => $build->id,
        ]);
        $this->assertRedirect('/tasks/today');

        $count = $this->Tasks->find()->where(['section_id' => $design->id])->count();
        $this->assertEquals(1, $count, 'Should be moved out.');

        $results = $this->childOrderedTasks(['section_id' => $build->id]);
        $expected = [$third->id, $fourth->id, $first->id];
        $this->assertOrder($expected, $results);
    }

    public function testMoveInsideSection()
    {
        $project = $this->makeProject('work', 1);
        $design = $this->makeProjectSection('design', $project->id, 0);
        $build = $this->makeProjectSection('build', $project->id, 1);

        $first = $this->makeTask('first', $project->id, 0, ['section_id' => $design->id]);
        $second = $this->makeTask('second', $project->id, 1, ['section_id' => $design->id]);

        $third = $this->makeTask('third', $project->id, 0, ['section_id' => $build->id]);
        $fourth = $this->makeTask('fourth', $project->id, 1, ['section_id' => $build->id]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/move", [
            'child_order' => 1,
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->childOrderedTasks(['section_id' => $design->id]);
        $expected = [$second->id, $first->id];
        $this->assertOrder($expected, $results);

        $results = $this->childOrderedTasks(['section_id' => $build->id]);
        $expected = [$third->id, $fourth->id];
        $this->assertOrder($expected, $results);
    }

    public function testMoveOutOfSection()
    {
        $project = $this->makeProject('work', 1);
        $design = $this->makeProjectSection('design', $project->id, 0);

        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 0, ['section_id' => $design->id]);
        $third = $this->makeTask('third', $project->id, 1, ['section_id' => $design->id]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$third->id}/move", [
            'child_order' => 0,
            'section_id' => null,
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->childOrderedTasks(['section_id IS' => null]);
        $expected = [$third->id, $first->id];
        $this->assertOrder($expected, $results);

        $results = $this->childOrderedTasks(['section_id' => $design->id]);
        $expected = [$second->id];
        $this->assertOrder($expected, $results);
    }
}
