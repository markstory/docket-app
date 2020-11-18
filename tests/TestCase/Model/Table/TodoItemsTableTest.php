<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TodoItemsTable;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * App\Model\Table\TodoItemsTable Test Case
 */
class TodoItemsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TodoItemsTable
     */
    protected $TodoItems;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.TodoItems',
        'app.Projects',
        'app.TodoComments',
        'app.TodoSubtasks',
        'app.TodoLabels',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TodoItems') ? [] : ['className' => TodoItemsTable::class];
        $this->TodoItems = $this->getTableLocator()->get('TodoItems', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TodoItems);

        parent::tearDown();
    }

    protected function makeProject($title, $userId)
    {
        $project = $this->TodoItems->Projects->newEntity([
            'user_id' => $userId,
            'name' => $title,
            'color' => '663366',
            'order' => 0,
        ]);

        return $this->TodoItems->Projects->saveOrFail($project);
    }

    protected function makeItem($title, $projectId, $order)
    {
        $todoItem = $this->TodoItems->newEntity([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ]);

        return $this->TodoItems->saveOrFail($todoItem);
    }

    /**
     * Make sure that items all have the same scope.
     */
    public function testReorderChildFailMultipleScopes()
    {
        $this->makeProject('First', 1);
        $one = $this->makeItem('First', 1, 0);
        $two = $this->makeItem('Second', 2, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('multiple projects');
        $desired = [$two, $one];
        $this->TodoItems->reorder('child', $desired);
    }

    public function testReorderDayScope()
    {
        $project = $this->makeProject('other', 1);

        $one = $this->makeItem('First', 1, 9);
        $two = $this->makeItem('Second', 1, 10);
        $three = $this->makeItem('P2 First', 2, 2);
        $four = $this->makeItem('P2 Second', 2, 3);

        $desired = [
            $one, $four, $two, $three
        ];
        $this->TodoItems->reorder('day', $desired);
        $results = $this->TodoItems->find()->orderAsc('day_order')->toArray();
        foreach ($results as $i => $result) {
            $this->assertEquals($desired[$i]->id, $result->id, "Failed on index={$i}");
        }
    }
}
