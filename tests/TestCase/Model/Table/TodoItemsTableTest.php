<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TodoItemsTable;
use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * App\Model\Table\TodoItemsTable Test Case
 */
class TodoItemsTableTest extends TestCase
{
    use FactoryTrait;

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

    /**
     * Make sure that items all have the same scope.
     */
    public function testReorderChildFailMultipleScopes()
    {
        $first = $this->makeProject('First', 1);
        $second = $this->makeProject('Second', 1);

        $one = $this->makeItem('First', $first->id, 0);
        $two = $this->makeItem('Second', $second->id, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('multiple projects');
        $desired = [$two, $one];
        $this->TodoItems->reorder('child', $desired);
    }

    public function testReorderDayScope()
    {
        $home = $this->makeProject('Home', 1);
        $work = $this->makeProject('Work', 1);

        $one = $this->makeItem('First', $home->id, 9);
        $two = $this->makeItem('Second',$home->id, 10);
        $three = $this->makeItem('P2 First', $work->id, 2);
        $four = $this->makeItem('P2 Second', $work->id, 3);

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
