<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TodoSubtasksController;
use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TodoSubtasksController Test Case
 *
 * @uses \App\Controller\TodoSubtasksController
 */
class TodoSubtasksControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.TodoSubtasks',
        'app.TodoItems',
        'app.Projects',
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->TodoSubtasks = TableRegistry::get('TodoSubtasks');
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $project = $this->makeProject('work', 1);
        $item = $this->makeItem('Cut grass', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks", [
            'title' => 'first subtask',
        ]);
        $this->assertRedirect("/todos/{$item->id}/view");
        $tasks = $this->TodoSubtasks->find()->where(['TodoSubtasks.todo_item_id' => $item->id]);
        $this->assertCount(1, $tasks);
    }

    /**
     * Test add permission fail
     *
     * @return void
     */
    public function testAddPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $item = $this->makeItem('Cut grass', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks", [
            'title' => 'first subtask',
        ]);
        $this->assertResponseCode(403);
        $tasks = $this->TodoSubtasks->find()->where(['TodoSubtasks.todo_item_id' => $item->id]);
        $this->assertCount(0, $tasks);
    }

    /**
     * Test toggle method
     *
     * @return void
     */
    public function testToggle(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test toggle method
     *
     * @return void
     */
    public function testTogglePermissions(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    public function testReorderSuccess()
    {
        $project = $this->makeProject('work', 1);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $first = $this->makeSubtask('start mower', $item->id);
        $second = $this->makeSubtask('cut', $item->id);
        $third = $this->makeSubtask('done', $item->id);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$third->id, $first->id, $second->id];
        $this->post("/todos/{$item->id}/subtasks/reorder", [
            'items' => $expected,
        ]);
        $this->assertRedirect("/todos/{$item->id}/view");

        $results = $this->TodoSubtasks->find()->orderAsc('ranking')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderCrossItem()
    {
        $project = $this->makeProject('work', 1);
        $first = $this->makeItem('first', $project->id, 0);
        $second = $this->makeItem('second', $project->id, 3);

        $subFirst = $this->makeSubtask('first sub', $first->id);
        $subSecond = $this->makeSubtask('second sub', $second->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$first->id}/subtasks/reorder", [
            'items' => [$subFirst->id, $subSecond->id],
        ]);
        $this->assertResponseCode(404);
    }
}
