<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TodoItemsController;
use App\Test\TestCase\FactoryTrait;
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
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
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
        $second = $this->makeItem('second', $project->id, 3);
        $third = $this->makeItem('third', $other->id, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/todos/reorder', [
            'scope' => 'day',
            'items' => [$third->id, $second->id, $first->id],
        ]);
        $this->assertResponseCode(404);
    }
}
