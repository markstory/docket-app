<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TodoItemsController;
use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TodoItemsController Test Case
 *
 * @uses \App\Controller\TodoItemsController
 */
class TodoItemsControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Users',
        'app.TodoItems',
        'app.Projects',
        'app.TodoComments',
        'app.TodoSubtasks',
        'app.TodoItemsTodoLabels',
        'app.TodoLabels',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->TodoItems = TableRegistry::get('TodoItems');
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
        $first = $this->makeItem('first', $project->id, 0, ['due_on' => $tomorrow]);
        $second = $this->makeItem('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeItem('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow
        ]);

        $this->login();
        $this->get('/todos');

        $this->assertResponseOk();
        $this->assertSame('upcoming', $this->viewVariable('view'));

        $items = $this->viewVariable('todoItems')->toArray();
        $this->assertCount(2, $items);
        $ids = array_map(function ($i) { return $i->id; }, $items);
        $this->assertEquals([$first->id, $second->id], $ids);
    }

    public function testIndexToday()
    {
        $today = new FrozenDate('today');
        $tomorrow = new FrozenDate('tomorrow');
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0, ['due_on' => $today]);
        $this->makeItem('second', $project->id, 3, ['due_on' => $tomorrow]);
        $this->makeItem('complete', $project->id, 0, [
            'completed' => true,
            'due_on' => $tomorrow
        ]);

        $this->login();
        $this->get('/todos/today');
        $this->assertResponseOk();
        $this->assertSame('today', $this->viewVariable('view'));

        $items = $this->viewVariable('todoItems')->toArray();
        $this->assertCount(1, $items);
        $ids = array_map(function ($i) { return $i->id; }, $items);
        $this->assertEquals([$first->id], $ids);
    }

    public function testIndexPermissions()
    {
        $tomorrow = new FrozenDate('tomorrow');
        $other = $this->makeProject('work', 2);
        $project = $this->makeProject('work', 1);

        $first = $this->makeItem('first', $project->id, 0, ['due_on' => $tomorrow]);
        $this->makeItem('first', $other->id, 3, ['due_on' => $tomorrow]);

        $this->login();
        $this->get('/todos/upcoming');
        $this->assertResponseOk();
        $this->assertSame('upcoming', $this->viewVariable('view'));

        $items = $this->viewVariable('todoItems')->toArray();
        $this->assertCount(1, $items);
        $ids = array_map(function ($i) { return $i->id; }, $items);
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
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->get("/todos/{$first->id}/view");
        $this->assertResponseOk();
        $var = $this->viewVariable('todoItem');
        $this->assertSame($var->title, $first->title);
    }

    public function testViewPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->get("/todos/{$first->id}/view");
        $this->assertResponseCode(403);
    }

    public function testAdd(): void
    {
        $project = $this->makeProject('work', 1);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/add", [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertResponseCode(302);

        $todo = $this->TodoItems->find()->firstOrFail();
        $this->assertSame('first todo', $todo->title);
    }

    public function testAddPermissions(): void
    {
        $project = $this->makeProject('work', 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/add", [
            'title' => 'first todo',
            'project_id' => $project->id,
        ]);
        $this->assertResponseCode(403);
    }

    public function testEdit(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$first->id}/edit", [
            'title' => 'updated',
        ]);
        $this->assertResponseCode(200);

        $todo = $this->TodoItems->get($first->id);
        $this->assertSame('updated', $todo->title);
    }

    public function testEditPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$first->id}/edit", [
            'title' => 'updated',
        ]);
        $this->assertResponseCode(403);
    }

    public function testDelete(): void
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$first->id}/delete");

        $this->assertRedirect('/todos');
        $this->assertFalse($this->TodoItems->exists(['TodoItems.id' => $first->id]));
    }

    public function testDeletePermission(): void
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$first->id}/delete");

        $this->assertTrue($this->TodoItems->exists(['TodoItems.id' => $first->id]));
    }

    public function testReorderSuccess()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 3);
        $third = $this->makeItem('third', $project->id, 6);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$third->id, $first->id, $second->id];
        $this->post('/todos/reorder', [
            'scope' => 'day',
            'items' => $expected,
        ]);
        $this->assertRedirect('/todos');

        $results = $this->TodoItems->find()->orderAsc('day_order')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderBadScope()
    {
        $this->login();
        $this->enableCsrfToken();
        $this->post('/todos/reorder', [
            'scope' => 'poop',
            'items' => [],
        ]);
        $this->assertResponseCode(400);
    }

    public function testReorderCrossOwner()
    {
        $project = $this->makeProject('work', 1);
        $other = $this->makeProject('work', 2);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 1);
        $third = $this->makeItem('third', $other->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/todos/reorder', [
            'scope' => 'day',
            'items' => [$third->id, $second->id, $first->id],
        ]);
        $this->assertResponseCode(404);
    }

    public function testMovePermissions()
    {
        $project = $this->makeProject('work', 2);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 1);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$second->id, $first->id];
        $this->post("/todos/{$second->id}/move", [
            'day_order' => 0,
        ]);
        $this->assertResponseCode(403);
    }

    public function testMoveInvalidDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/todos/{$first->id}/move", [
            'day_order' => 0,
            'due_on' => 'not a date'
        ]);

        $this->assertRedirect('/todos');
        $this->assertFlashElement('flash/error');
    }

    public function testMoveUpSameDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 1);
        $third = $this->makeItem('third', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$third->id, $first->id, $second->id];
        $this->post("/todos/{$third->id}/move", [
            'day_order' => 0,
        ]);
        $this->assertRedirect('/todos');
        $results = $this->TodoItems->find()->orderAsc('day_order')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
            $this->assertNull($results[$i]->due_on);
        }
    }

    public function testMoveDownSameDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 1);
        $third = $this->makeItem('third', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$second->id, $third->id, $first->id];
        $this->post("/todos/{$first->id}/move", [
            'day_order' => 2,
        ]);
        $this->assertRedirect('/todos');

        $results = $this->TodoItems->find()->orderAsc('day_order')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveDifferentDay()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0, ['due_on' => '2020-12-13']);
        $second = $this->makeItem('second', $project->id, 2, ['due_on' => '2020-12-13']);
        $third = $this->makeItem('third', $project->id, 0, ['due_on' => '2020-12-14']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$third->id}/move", [
            'day_order' => 2,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/todos');

        $results = $this->TodoItems
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
        $first = $this->makeItem('first', $project->id, 0, ['due_on' => '2020-12-13']);
        $second = $this->makeItem('second', $project->id, 2, ['due_on' => '2020-12-13']);
        $third = $this->makeItem('third', $project->id, 3, ['due_on' => '2020-12-13']);

        $new = $this->makeItem('new', $project->id, 0, ['due_on' => '2020-12-20']);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$new->id}/move", [
            'day_order' => 2,
            'due_on' => '2020-12-13',
        ]);
        $this->assertRedirect('/todos');

        $results = $this->TodoItems
            ->find()
            ->orderAsc('day_order')->toArray();
        $expected = [$first->id, $new->id, $second->id, $third->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveProjectUp()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 2);
        $third = $this->makeItem('third', $project->id, 3);
        $fourth = $this->makeItem('fourth', $project->id, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$fourth->id}/move", [
            'child_order' => 2,
        ]);
        $this->assertRedirect('/todos');

        $results = $this->TodoItems
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
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 1);
        $third = $this->makeItem('third', $project->id, 2);
        $fourth = $this->makeItem('fourth', $project->id, 3);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$first->id}/move", [
            'child_order' => 2,
        ]);
        $this->assertRedirect('/todos');

        $results = $this->TodoItems
            ->find()
            ->orderAsc('child_order')->toArray();
        $expected = [$second->id, $third->id, $first->id, $fourth->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}
