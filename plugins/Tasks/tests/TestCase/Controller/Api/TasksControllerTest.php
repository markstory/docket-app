<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use function Cake\Collection\collection;

/**
 * App\Controller\Api\TasksController Test Case
 */
class TasksControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * @var \Tasks\Model\Table\TasksTable
     */
    protected $Tasks;

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
    ];

    protected function dayOrderedTasks(array $conditions = [])
    {
        return $this->Tasks
            ->find()
            ->where($conditions)
            ->orderByAsc('evening')
            ->orderByAsc('day_order')
            ->toArray();
    }

    protected function childOrderedTasks(array $conditions = [])
    {
        return $this->Tasks
            ->find()
            ->where($conditions)
            ->orderByAsc('child_order')
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
        $this->Tasks = $this->fetchTable('Tasks.Tasks');
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $tomorrow = new Date('tomorrow');
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $second = $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow,
        ]);
        $this->loginApi(1);

        $this->get('/api/tasks.json');

        $this->assertResponseOk();
        $response = json_decode(strval($this->_response->getBody()), true);

        $this->assertArrayHasKey('tasks', $response);
        $this->assertArrayHasKey('calendarItems', $response);

        $this->assertCount(2, $response['tasks']);
        $ids = collection($response['tasks'])->extract('id')->toList();
        $this->assertEquals([$first->id, $second->id], $ids);
    }

    public function testIndexStartAndEnd(): void
    {
        $sixDays = new Date('+6 days');
        $eightDays = $sixDays->modify('+2 days');
        $tenDays = $eightDays->modify('+2 days');

        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $sixDays]);
        $second = $this->makeTask('second', $project->id, 1, ['due_on' => $eightDays]);
        $this->makeTask('third', $project->id, 2, ['due_on' => $tenDays]);
        $this->loginApi(1);

        // End range is not inclusive, but start is.
        $this->get("/api/tasks.json?start={$sixDays->format('Y-m-d')}&end={$tenDays->format('Y-m-d')}");

        $this->assertResponseOk();

        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(2, $items);
        $ids = collection($items)->extract('id')->toList();
        $this->assertEquals([$first->id, $second->id], $ids);
    }

    public function testIndexInvalidStart()
    {
        $this->loginApi(1);

        $this->get('/api/tasks.json/?start=nope');
        $this->assertResponseCode(404);
    }

    public function testIndexInvalidEnd()
    {
        $tomorrow = new Date('tomorrow');
        $this->loginApi(1);

        $this->get("/api/tasks.json/?start={$tomorrow->format('Y-m-d')}&end=nope");
        $this->assertResponseCode(404);
    }

    public function testIndexInvalidRange()
    {
        $start = new Date('tomorrow');
        $end = $start->modify('+61 days');
        $this->loginApi(1);

        $this->get("/api/tasks.json/?start={$start->format('Y-m-d')}&end={$end->format('Y-m-d')}");
        $this->assertResponseCode(404);
    }

    /**
     * Test today route
     *
     * @return void
     */
    public function testToday(): void
    {
        $today = new Date('today');
        $tomorrow = $today->modify('+1 day');
        $yesterday = $today->modify('-1 day');

        $project = $this->makeProject('work', 1);
        $overdue = $this->makeTask('overdue', $project->id, 0, ['due_on' => $yesterday]);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $today]);
        $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $today,
        ]);
        $this->loginApi(1);

        $this->get('/api/tasks/today.json');

        $this->assertResponseOk();
        $tasks = $this->viewVariable('tasks');
        $this->assertCount(2, $tasks);
        $this->assertEquals($today->format('Y-m-d'), $this->viewVariable('date')->format('Y-m-d'));

        $ids = collection($tasks)->extract('id')->toList();
        $this->assertEquals([$overdue->id, $first->id], $ids);
    }

    /**
     * Test day method with date param
     *
     * @return void
     */
    public function testDailyInvalidParam(): void
    {
        $this->makeProject('work', 1);
        $this->loginApi(1);

        $this->get('/api/tasks/day/nope.json');
        $this->assertResponseError();
    }

    public function testDailyPermissions()
    {
        $tomorrow = new Date('tomorrow');
        $other = $this->makeProject('work', 2);
        $project = $this->makeProject('work', 1);

        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $this->makeTask('first', $other->id, 3, ['due_on' => $tomorrow]);
        $this->loginApi(1);

        $this->get("/api/tasks/day/{$tomorrow->format('Y-m-d')}.json");

        $this->assertResponseOk();
        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(1, $items);
        $ids = array_map(function ($i) {

            return $i->id;
        }, $items);
        $this->assertEquals([$first->id], $ids);
    }

    public function testDailyToday()
    {
        $today = new Date('today');
        $tomorrow = new Date('tomorrow');
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $today]);
        $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow,
        ]);
        $this->loginApi(1);

        $this->get('/api/tasks/today.json');

        $this->assertResponseOk();

        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(1, $items);
        $ids = collection($items)->extract('id')->toList();
        $this->assertEquals([$first->id], $ids);
    }

    /**
     * Test day method with date param
     *
     * @return void
     */
    public function testDailyParam(): void
    {
        $today = new Date('today');
        $tomorrow = $today->modify('+1 day');
        $yesterday = $today->modify('-1 day');

        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $today]);
        $this->makeTask('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeTask('nope', $project->id, 3, ['due_on' => $yesterday]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $today,
        ]);
        $this->loginApi(1);

        $this->get("/api/tasks/day/{$today->format('Y-m-d')}.json");

        $this->assertResponseOk();
        $tasks = $this->viewVariable('tasks');
        $this->assertCount(1, $tasks);
        $this->assertEquals($today->format('Y-m-d'), $this->viewVariable('date')->format('Y-m-d'));

        $ids = collection($tasks)->extract('id')->toList();
        $this->assertEquals([$first->id], $ids);
    }

    public function testDailyOverdueParam(): void
    {
        $today = new Date('today');
        $yesterday = $today->modify('-1 day');

        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $today]);
        $overdue = $this->makeTask('nope', $project->id, 3, ['due_on' => $yesterday]);
        $this->makeTask('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $today,
        ]);
        $this->loginApi(1);

        $this->get("/api/tasks/day/{$today->format('Y-m-d')}.json?overdue=1");

        $this->assertResponseOk();
        $tasks = $this->viewVariable('tasks');
        $this->assertCount(2, $tasks);
        $ids = collection($tasks)->extract('id')->toList();
        $this->assertEquals([$overdue->id, $first->id], $ids);
    }

    public function testDailyCalendarItems(): void
    {
        $timezone = 'America/New_York';

        $users = $this->fetchTable('Users');
        $user = $users->get(1);
        $user->timezone = $timezone;
        $users->saveOrFail($user);

        $now = new DateTime('now', $timezone);
        $startOfDay = $now->setTime(0, 0, 1);
        $endOfDay = $now->setTime(23, 59, 58);

        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary');
        $early = $this->makeCalendarItem($source->id, [
            'title' => 'early event',
            'provider_id' => 'event-1',
            'start_time' => $startOfDay->setTimezone('UTC'),
            'end_time' => $startOfDay->modify('+1 hour')->setTimezone('UTC'),
            'start_date' => null,
            'end_date' => null,
        ]);
        $late = $this->makeCalendarItem($source->id, [
            'title' => 'late event',
            'provider_id' => 'event-2',
            'start_time' => $endOfDay->modify('-1 hour')->setTimezone('UTC'),
            'end_time' => $endOfDay->setTimezone('UTC'),
            'start_date' => null,
            'end_date' => null,
        ]);
        $allDay = $this->makeCalendarItem($source->id, [
            'title' => 'Bob birthday',
            'provider_id' => 'event-3',
            'start_date' => new Date($startOfDay),
            'end_date' => (new Date($startOfDay))->modify('+1 day'),
            'start_time' => null,
            'end_time' => null,
            'all_day' => true,
        ]);

        // Events not included.
        $this->makeCalendarItem($source->id, [
            'title' => 'Tomorrow day event',
            'provider_id' => 'event-4',
            'start_date' => (new Date($startOfDay))->modify('+1 day'),
            'end_date' => (new Date($startOfDay))->modify('+2 day'),
            'start_time' => null,
            'end_time' => null,
            'all_day' => true,
        ]);
        $this->makeCalendarItem($source->id, [
            'title' => 'Tomorrow time event',
            'provider_id' => 'event-5',
            'start_time' => $endOfDay->modify('+1 hour')->setTimezone('UTC'),
            'end_time' => $endOfDay->modify('+2 hours')->setTimezone('UTC'),
            'start_date' => null,
            'end_date' => null,
        ]);
        $this->loginApi(1);

        $this->get("/api/tasks/day/{$startOfDay->format('Y-m-d')}.json");

        $this->assertResponseOk();

        // Check the view variables.
        $items = $this->viewVariable('calendarItems');
        $this->assertCount(3, $items);
        $itemIds = collection($items)->extract('id')->toList();
        $this->assertEquals([$allDay->id, $early->id, $late->id], $itemIds);

        // Check the time formatting
        $this->assertResponseContains('early event');
        $this->assertResponseContains('late event');
    }

    public function testIndexCalendarItems(): void
    {
        $tomorrow = new Date('tomorrow');

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

        $tomorrow = new DateTime('tomorrow');
        $lunch = $this->makeCalendarItem($source->id, [
            'title' => 'Lunch',
            'provider_id' => 'event-2',
            'start_time' => $tomorrow->setTime(12, 0),
            'end_time' => $tomorrow->setTime(13, 0),
            'all_day' => false,
        ]);
        $this->loginApi(1);

        $this->get('/api/tasks.json');

        $this->assertResponseOk();

        $items = $this->viewVariable('calendarItems')->toArray();
        $this->assertCount(2, $items);
        $this->assertEquals($allDay->id, $items[0]->id);
        $this->assertEquals($lunch->id, $items[1]->id);
    }

    public function testIndexPermissions()
    {
        $tomorrow = new Date('tomorrow');
        $other = $this->makeProject('work', 2);
        $project = $this->makeProject('work', 1);

        $first = $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $this->makeTask('first', $other->id, 3, ['due_on' => $tomorrow]);
        $this->loginApi(1);

        $this->get('/api/tasks/upcoming.json');

        $this->assertResponseOk();
        $items = $this->viewVariable('tasks')->toArray();
        $this->assertCount(1, $items);
        $ids = array_map(function ($i) {

            return $i->id;
        }, $items);
        $this->assertEquals([$first->id], $ids);
    }

    public function testIndexInvalidParameter()
    {
        $tomorrow = new Date('tomorrow');
        $project = $this->makeProject('work', 1);
        $this->makeTask('first', $project->id, 0, ['due_on' => $tomorrow]);
        $this->loginApi(1);

        $this->get('/api/tasks.json?start=nope');

        $this->assertResponseCode(400);
    }

    /**
     * Test deleted listing
     *
     * @return void
     */
    public function testDeleted(): void
    {
        $tomorrow = new Date('tomorrow');
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
        $this->loginApi(1);

        $this->get('/api/tasks/deleted.json');

        $this->assertResponseOk();

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
        $this->loginApi(1);

        $this->get("/api/tasks/{$first->id}/view.json");

        $this->assertResponseOk();
        $var = $this->viewVariable('task');
        $this->assertSame($var->title, $first->title);
    }

    public function testViewPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->get("/api/tasks/{$first->id}/view.json");

        $this->assertResponseCode(403);
    }

    public function testAdd(): void
    {
        $project = $this->makeProject('work', 1);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);

        $this->assertResponseOk();
        $task = $this->viewVariable('task');
        $this->assertSame('first todo', $task->title);
        $this->assertNotEmpty($task->project);
        $this->assertSame('work', $task->project->slug);

        $project = $this->Tasks->Projects->get($project->id);
        $this->assertEquals(1, $project->incomplete_task_count);
    }

    public function testAddToBottom(): void
    {
        $project = $this->makeProject('work', 1);
        $this->makeTask('existing', $project->id, 3, [
            'due_on' => '2020-12-17',
            'day_order' => 9,
        ]);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'project_id' => $project->id,
            'due_on' => '2020-12-17',
        ]);

        $this->assertResponseOk();
        $todo = $this->Tasks->findByTitle('first todo')->firstOrFail();
        $this->assertSame(4, $todo->child_order);
        $this->assertSame(10, $todo->day_order);
    }

    public function testAddToSection(): void
    {
        $project = $this->makeProject('work', 1);
        $section = $this->makeProjectSection('release', $project->id);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'section_id' => $section->id,
            'project_id' => $project->id,
            'due_on' => '2020-12-17',
        ]);

        $this->assertResponseOk();
        $todo = $this->Tasks->findByTitle('first todo')->firstOrFail();
        $this->assertSame(1, $todo->child_order);
        $this->assertSame(1, $todo->day_order);
        $this->assertSame($section->id, $todo->section_id);
    }

    public function testAddWithSubtasks(): void
    {
        $project = $this->makeProject('work', 1);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'project_id' => $project->id,
            'subtasks' => [
                ['title' => 'first subtask', 'ranking' => 0],
                ['title' => 'second subtask', 'ranking' => 1],
            ],
        ]);
        $this->assertResponseOk();

        $todo = $this->Tasks->find()->contain('Subtasks')->firstOrFail();
        $this->assertSame('first todo', $todo->title);
        $this->assertCount(2, $todo->subtasks);
        $this->assertEquals('first subtask', $todo->subtasks[0]->title);
    }

    public function testAddWithIncompleteSubtask(): void
    {
        $project = $this->makeProject('work', 1);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'project_id' => $project->id,
            '_subtaskadd' => 'first subtask',
        ]);
        $this->assertResponseOk();

        $todo = $this->Tasks->find()->contain('Subtasks')->firstOrFail();
        $this->assertSame('first todo', $todo->title);
        $this->assertCount(1, $todo->subtasks);
        $this->assertEquals('first subtask', $todo->subtasks[0]->title);
    }

    public function testAddToSectionInDifferentProject(): void
    {
        $home = $this->makeProject('home', 1);
        $project = $this->makeProject('work', 1);
        $section = $this->makeProjectSection('release', $project->id);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'section_id' => $section->id,
            'project_id' => $home->id,
        ]);

        $this->assertResponseCode(400);
        $error = $this->viewVariable('errors');
        $this->assertNotEmpty($error);
    }

    public function testAddPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $this->loginApi(1);

        $this->post('/api/tasks/add.json', [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertResponseCode(403);
    }

    public function testEdit(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit.json", [
            'title' => 'updated',
            'evening' => true,
        ]);

        $this->assertResponseOk();
        $updated = $this->viewVariable('task');
        $this->assertSame('updated', $updated->title);
        $this->assertTrue($updated->evening);
        // Need to have the project as well.
        $this->assertSame('work', $updated->project->slug);
    }

    public function testEditDueOnString(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->assertNull($first->due_on);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit.json", [
            'due_on_string' => 'tomorrow',
        ]);

        $this->assertResponseOk();
        $updated = $this->viewVariable('task');
        $this->assertEquals(Date::parse('tomorrow'), $updated->due_on);
    }

    public function testEditSubtasks(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->assertNull($first->due_on);

        $this->loginApi(1);
        $this->post("/api/tasks/{$first->id}/edit.json", [
            'due_on_string' => 'tomorrow',
            'subtasks' => [
                ['title' => 'first subtask'],
                ['title' => 'second subtask'],
            ],
        ]);

        $this->assertResponseOk();
        $task = $this->Tasks->get($first->id, contain: 'Subtasks');
        $this->assertCount(2, $task->subtasks);
        $this->assertEquals('first subtask', $task->subtasks[0]->title);
        $this->assertEquals('second subtask', $task->subtasks[1]->title);
    }

    public function testEditSubtasksIncomplete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->assertNull($first->due_on);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit.json", [
            'due_on_string' => 'tomorrow',
            '_subtaskadd' => 'first subtask',
        ]);

        $this->assertResponseOk();
        $task = $this->Tasks->get($first->id, contain: 'Subtasks');
        $this->assertNotEmpty($task);
        $this->assertCount(1, $task->subtasks);
        $this->assertEquals('first subtask', $task->subtasks[0]->title);
    }

    public function testEditApiTokenDueOnEmptySubtasks(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $subtask = $this->makeSubtask('first subtask', $first->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'due_on' => Date::parse('tomorrow')->format('Y-m-d'),
            'subtasks' => [],
        ]);
        $this->assertResponseOk();

        $updated = $this->viewVariable('task');
        $this->assertCount(1, $updated->subtasks);
        $reload = $this->Tasks->Subtasks->get($subtask->id);
        $this->assertNotEmpty($reload);
    }

    public function testEditProject(): void
    {
        $project = $this->makeProject('work', 1);
        $home = $this->makeProject('home', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'project_id' => $home->id,
        ]);
        $this->assertResponseOk();

        $updated = $this->viewVariable('task');
        $this->assertEquals($updated->project_id, $home->id);
        $this->assertEquals($updated->project->id, $home->id);
    }

    public function testEditValidation(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);
        $this->disableErrorHandlerMiddleware();

        $this->post("/api/tasks/{$first->id}/edit", [
            'title' => '',
            'evening' => true,
        ]);

        $this->assertResponseCode(422);
        $this->assertResponseContains('errors');
    }

    public function testEditCreateSubtasks(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'evening' => true,
            'subtasks' => [
                ['title' => 'first step'],
                ['title' => 'second step'],
            ],
        ]);

        $this->assertResponseOk();
        /** @var \Tasks\Model\Entity\Task $updated */
        $updated = $this->viewVariable('task');
        $this->assertCount(2, $updated->subtasks);
        $this->assertEquals(2, $updated->subtask_count);
        $this->assertEquals(0, $updated->complete_subtask_count);
        $this->assertSame('first step', $updated->subtasks[0]->title);
        $this->assertEquals(1, $updated->subtasks[0]->ranking);
        $this->assertFalse($updated->subtasks[0]->completed);

        $this->assertSame('second step', $updated->subtasks[1]->title);
        $this->assertEquals(2, $updated->subtasks[1]->ranking);
        $this->assertFalse($updated->subtasks[1]->completed);
    }

    public function testEditCreateSubtasksNoBlank(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit.json", [
            'title' => 'updated',
            'evening' => true,
            'subtasks' => [
                ['title' => 'first step'],
                ['title' => ''],
            ],
        ]);

        $this->assertResponseOk();
        $updated = $this->viewVariable('task');
        $this->assertCount(1, $updated->subtasks);
        $this->assertSame('first step', $updated->subtasks[0]->title);
        $this->assertEquals(1, $updated->subtasks[0]->ranking);
        $this->assertFalse($updated->subtasks[0]->completed);
    }

    public function testEditUpdateSubtasks(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $sub = $this->makeSubtask('first step', $first->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'subtasks' => [
                ['id' => $sub->id, 'title' => 'step one!', 'completed' => true],
                ['title' => 'step three'],
            ],
        ]);

        $this->assertResponseOk();
        $updated = $this->viewVariable('task');
        $this->assertCount(2, $updated->subtasks);
        $this->assertSame('step one!', $updated->subtasks[0]->title);
        $this->assertTrue($updated->subtasks[0]->completed);

        $this->assertSame('step three', $updated->subtasks[1]->title);
        $this->assertEquals(1, $updated->subtasks[1]->ranking);
        $this->assertFalse($updated->subtasks[1]->completed);
    }

    public function testEditUpdateSubtasksRemove(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $sub = $this->makeSubtask('first step', $first->id, 0);
        // This subtask isn't part of the update.
        $this->makeSubtask('second step', $first->id, 1);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'subtasks' => [
                ['id' => $sub->id, 'title' => $sub->title],
            ],
        ]);

        $this->assertResponseOk();
        $updated = $this->viewVariable('task');
        $this->assertCount(1, $updated->subtasks);
        $this->assertEquals(1, $updated->subtask_count);
        $this->assertSame($sub->title, $updated->subtasks[0]->title);
        $this->assertFalse($updated->subtasks[0]->completed);
    }

    public function testEditPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'title' => 'updated',
        ]);
        $this->assertResponseCode(403);
    }

    public function testEditProjectPermission(): void
    {
        $other = $this->makeProject('other work', 2);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/edit", [
            'title' => 'updated',
            'project_id' => $other->id,
        ]);

        $this->assertResponseOk();
        $todo = $this->Tasks->get($first->id);
        $this->assertSame('updated', $todo->title);
        $this->assertNull($todo->section_id, 'Should blank section because project is different.');
    }

    public function testDelete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/delete");

        $this->assertResponseOk();
        $deleted = $this->Tasks->get($first->id, deleted: true);
        $this->assertNotNull($deleted->deleted_at);
    }

    public function testDeleteCannotDeleteAgain(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => DateTime::now()]);
        $this->loginApi(1);

        $this->post("/tasks/{$first->id}/delete");
        $this->assertResponseCode(404);
    }

    public function testDeletePermission(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/delete");

        $deleted = $this->Tasks->get($first->id);
        $this->assertNull($deleted->deleted_at);
    }

    public function testUndelete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => DateTime::now()]);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/undelete");

        $this->assertResponseOk();
        $deleted = $this->Tasks->get($first->id);
        $this->assertNull($deleted->deleted_at);
    }

    public function testUndeletePermission(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0, ['deleted_at' => DateTime::now()]);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/undelete");

        $this->assertResponseCode(403);
    }

    public function testCompletePermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/complete");
        $this->assertResponseCode(403);

        $todo = $this->Tasks->get($first->id);
        $this->assertFalse($todo->completed);
    }

    public function testComplete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/complete");
        $this->assertResponseOk();

        $todo = $this->Tasks->get($first->id);
        $this->assertTrue($todo->completed);
    }

    public function testIncompletePermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0, ['completed' => true]);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/incomplete");
        $this->assertResponseCode(403);

        $todo = $this->Tasks->get($first->id);
        $this->assertTrue($todo->completed);
    }

    public function testIncomplete(): void
    {
        $this->makeApiToken(1);
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/incomplete");
        $this->assertResponseOk();

        $todo = $this->Tasks->get($first->id);
        $this->assertFalse($todo->completed);
    }

    public function testMovePermissions()
    {
        $project = $this->makeProject('work', 2);
        $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $this->loginApi(1);

        $this->post("/api/tasks/{$second->id}/move", [
            'day_order' => 0,
        ]);
        $this->assertResponseCode(403);
    }

    public function testMoveInvalidDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/move", [
            'day_order' => 0,
            'due_on' => 'not a date',
        ]);
        $this->assertResponseCode(422);
    }

    public function testMoveInvalidOrder()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/move", [
            'day_order' => -1,
        ]);

        $this->assertResponseCode(422);
    }

    public function testMoveUpSameDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $third = $this->makeTask('third', $project->id, 2);
        $this->loginApi(1);

        $expected = [$third->id, $first->id, $second->id];
        $this->post("/api/tasks/{$third->id}/move", [
            'day_order' => 0,
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $expected = [$second->id, $third->id, $first->id];
        $this->post("/api/tasks/{$first->id}/move", [
            'day_order' => 2,
        ]);

        $this->assertResponseOk();
        $results = $this->dayOrderedTasks();
        $this->assertOrder($expected, $results);
    }

    public function testMoveDownTooLarge()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 1);
        $third = $this->makeTask('third', $project->id, 2);

        $this->loginApi(1);
        $expected = [$second->id, $third->id, $first->id];
        $this->post("/api/tasks/{$first->id}/move", [
            'day_order' => 1000,
        ]);
        $this->assertResponseOk();

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
        $this->loginApi(1);

        $this->post("/api/tasks/{$fourth->id}/move", [
            'day_order' => 1,
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$third->id}/move", [
            'day_order' => 1,
            'due_on' => '2020-12-13',
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$new->id}/move", [
            'day_order' => 1,
            'due_on' => '2020-12-13',
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$new->id}/move", [
            'day_order' => 0,
            'due_on' => '2020-12-13',
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$new->id}/move", [
            'day_order' => 3,
            'due_on' => '2020-12-13',
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$third->id}/move", [
            'day_order' => 1,
            'due_on' => '2020-12-13',
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $expected = [$second->id, $third->id, $first->id];
        $this->post("/api/tasks/{$first->id}/move", [
            'day_order' => 1,
            'evening' => true,
        ]);

        $this->assertResponseOk();
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

        $this->loginApi(1);
        $expected = [$third->id, $second->id, $first->id, $fourth->id];
        $this->post("/api/tasks/{$first->id}/move", [
            'day_order' => 1,
            'evening' => true,
        ]);
        $this->assertResponseOk();
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
        $this->loginApi(1);

        $expected = [$first->id, $third->id, $second->id, $fourth->id, $fifth->id];
        $this->post("/api/tasks/{$third->id}/move", [
            'day_order' => 1,
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $expected = [$first->id, $third->id, $second->id];
        $this->post("/api/tasks/{$third->id}/move", [
            'day_order' => 1,
            'evening' => false,
        ]);

        $this->assertResponseOk();
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

        $this->loginApi(1);
        $this->post("/api/tasks/{$fourth->id}/move", [
            'child_order' => 1,
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/move", [
            'child_order' => 2,
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/move", [
            'child_order' => 2,
            'section_id' => null,
        ]);

        $this->assertResponseOk();
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
            /*
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
            */
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

        $this->loginApi(1);
        $this->post("/api/tasks/{$first->id}/move", $operation);

        $this->assertResponseCode(422);
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/move", [
            'child_order' => 2,
            'section_id' => $build->id,
        ]);

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->post("/api/tasks/{$first->id}/move", [
            'child_order' => 1,
        ]);
        $this->assertResponseOk();

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
        $this->loginApi(1);

        $this->post("/api/tasks/{$third->id}/move", [
            'child_order' => 0,
            'section_id' => null,
        ]);
        $this->assertResponseOk();

        $results = $this->childOrderedTasks(['section_id IS' => null]);
        $expected = [$third->id, $first->id];
        $this->assertOrder($expected, $results);

        $results = $this->childOrderedTasks(['section_id' => $design->id]);
        $expected = [$second->id];
        $this->assertOrder($expected, $results);
    }
}
