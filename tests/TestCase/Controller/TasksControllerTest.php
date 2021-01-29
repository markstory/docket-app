<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenDate;
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
        'app.Subtasks',
        'app.LabelsTasks',
        'app.Labels',
    ];

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

    public function testAdd(): void
    {
        $project = $this->makeProject('work', 1);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertResponseCode(302);

        $todo = $this->Tasks->find()->firstOrFail();
        $this->assertSame('first todo', $todo->title);

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

        $this->login();
        $this->enableCsrfToken();
        $this->post('/tasks/add', [
            'title' => 'first todo',
            'project_id' => $project->id,
            'due_on' => '2020-12-17',
        ]);
        $this->assertResponseCode(302);

        $todo = $this->Tasks->findByTitle('first todo')->firstOrFail();
        $this->assertSame(4, $todo->child_order);
        $this->assertSame(10, $todo->day_order);
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
        $this->post("/tasks/{$first->id}/edit", [
            'title' => 'updated',
        ]);
        $this->assertResponseCode(200);

        $todo = $this->Tasks->get($first->id);
        $this->assertSame('updated', $todo->title);
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

    public function testDelete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/delete");

        $this->assertRedirect('/tasks/today');
        $this->assertFalse($this->Tasks->exists(['Tasks.id' => $first->id]));
    }

    public function testDeletePermission(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeTask('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/tasks/{$first->id}/delete");

        $this->assertTrue($this->Tasks->exists(['Tasks.id' => $first->id]));
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

        $this->assertRedirect('/tasks/today');
        $this->assertFlashElement('flash/error');
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
        $this->assertRedirect('/tasks/today');
        $results = $this->Tasks->find()->orderAsc('day_order')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
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
        $this->assertRedirect('/tasks/today');

        $results = $this->Tasks->find()->orderAsc('day_order')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveDownDuplicateOrder()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeTask('first', $project->id, 0);
        $second = $this->makeTask('second', $project->id, 2);
        $third = $this->makeTask('a third', $project->id, 2);
        $fourth = $this->makeTask('fourth', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$first->id, $fourth->id, $second->id, $third->id];
        $this->post("/tasks/{$fourth->id}/move", [
            'day_order' => 1,
        ]);
        $this->assertRedirect('/tasks/today');

        $results = $this->Tasks->find()->orderAsc('day_order')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
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

        $results = $this->Tasks
            ->find()
            ->orderAsc('day_order')->toArray();
        $expected = [$first->id, $third->id, $second->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
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

        $results = $this->Tasks
            ->find()
            ->orderAsc('day_order')->toArray();
        $expected = [$first->id, $new->id, $second->id, $third->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id, "failed on {$i}");
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

        $results = $this->Tasks
            ->find()
            ->orderAsc('day_order')->toArray();
        $expected = [$new->id, $first->id, $second->id, $third->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
        }

        $expected = [0, 1, 2, 3];
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->day_order);
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

        $results = $this->Tasks
            ->find()
            ->orderAsc('day_order')->toArray();
        $expected = [$first->id, $third->id, $second->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
            $this->assertEquals('2020-12-13', $results[$i]->due_on->format('Y-m-d'));
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

        $results = $this->Tasks
            ->find()
            ->orderAsc('child_order')->toArray();
        $expected = [$first->id, $fourth->id, $second->id, $third->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
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

        $results = $this->Tasks
            ->find()
            ->orderAsc('child_order')->toArray();
        $expected = [$second->id, $third->id, $first->id, $fourth->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}
