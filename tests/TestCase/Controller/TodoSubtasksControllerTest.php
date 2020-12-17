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

    public function testAddAppendRanking(): void
    {
        $project = $this->makeProject('work', 1);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $this->makeSubtask('get mower', $project->id, 4);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks", [
            'title' => 'start mower',
        ]);
        $this->assertRedirect("/todos/{$item->id}/view");
        $task = $this->TodoSubtasks->findByTitle('start mower')->firstOrFail();
        $this->assertSame(5, $task->ranking);
    }

    /**
     * Test toggle method
     *
     * @return void
     */
    public function testToggle(): void
    {
        $project = $this->makeProject('work', 1);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $subtask = $this->makeSubtask('Get mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$subtask->id}/toggle");
        $this->assertRedirect("/todos/{$item->id}/view");
        $tasks = $this->TodoSubtasks->find()->where(['TodoSubtasks.todo_item_id' => $item->id]);
        $this->assertTrue($tasks->first()->completed);
    }

    /**
     * Test toggle method
     *
     * @return void
     */
    public function testTogglePermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $subtask = $this->makeSubtask('Get mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$subtask->id}/toggle");
        $this->assertResponseCode(403);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $project = $this->makeProject('work', 1);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $subtask = $this->makeSubtask('Get mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$subtask->id}/edit", [
            'title' => 'Updated'
        ]);
        $this->assertResponseOk();
        $update = $this->TodoSubtasks->get($subtask->id);
        $this->assertSame('Updated', $update->title);
    }

    public function testEditPermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $subtask = $this->makeSubtask('Get mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$subtask->id}/edit", [
            'title' => 'Updated'
        ]);
        $this->assertResponseCode(403);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $project = $this->makeProject('work', 1);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $subtask = $this->makeSubtask('Get mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$subtask->id}/delete");
        $this->assertRedirect("/todos/{$item->id}/view");
        $this->assertCount(0, $this->TodoSubtasks->find()->all());
    }

    public function testDeletePermissions(): void
    {
        $project = $this->makeProject('work', 2);
        $item = $this->makeItem('Cut grass', $project->id, 0);
        $subtask = $this->makeSubtask('Get mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$subtask->id}/delete");
        $this->assertResponseCode(403);
    }

    public function testMoveNoData()
    {
        $home = $this->makeProject('Home', 1, 0);
        $item = $this->makeItem('Cut grass', $home->id, 0);
        $first = $this->makeSubtask('start mower', $item->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/todos/{$item->id}/subtasks/{$first->id}/move");
        $this->assertRedirect("/todos/{$item->id}/view");
        $this->assertFlashElement('flash/error');
    }

    public function testMoveDown()
    {
        $home = $this->makeProject('Home', 1, 0);
        $item = $this->makeItem('Cut grass', $home->id, 0);
        $first = $this->makeSubtask('start mower', $item->id, 0);
        $second = $this->makeSubtask('cut', $item->id, 1);
        $third = $this->makeSubtask('done', $item->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$first->id}/move", [
            'ranking' => 1,
        ]);
        $this->assertRedirect("/todos/{$item->id}/view");

        $results = $this->TodoSubtasks->find()->orderAsc('ranking')->toArray();
        $expected = [$second->id, $first->id, $third->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveUp()
    {
        $home = $this->makeProject('Home', 1, 0);
        $item = $this->makeItem('Cut grass', $home->id, 0);
        $first = $this->makeSubtask('start mower', $item->id, 0);
        $second = $this->makeSubtask('cut', $item->id, 1);
        $third = $this->makeSubtask('done', $item->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/todos/{$item->id}/subtasks/{$third->id}/move", [
            'ranking' => 0,
        ]);
        $this->assertRedirect("/todos/{$item->id}/view");

        $results = $this->TodoSubtasks->find()->orderAsc('ranking')->toArray();
        $expected = [$third->id, $first->id, $second->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}
