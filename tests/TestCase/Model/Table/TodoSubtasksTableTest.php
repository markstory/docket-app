<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TodoSubtasksTable;
use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TodoSubtasksTable Test Case
 */
class TodoSubtasksTableTest extends TestCase
{
    use FactoryTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\TodoSubtasksTable
     */
    protected $TodoSubtasks;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Projects',
        'app.TodoSubtasks',
        'app.TodoItems',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TodoSubtasks') ? [] : ['className' => TodoSubtasksTable::class];
        $this->TodoSubtasks = $this->getTableLocator()->get('TodoSubtasks', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TodoSubtasks);

        parent::tearDown();
    }

    public function testReorder()
    {
        $home = $this->makeProject('Home', 1, 0);
        $laundry = $this->makeItem('Do laundry', $home->id, 3);
        $first = $this->makeSubtask('Get clothes', $laundry->id);
        $second = $this->makeSubtask('Open machine', $laundry->id);
        $third = $this->makeSubtask('Put in clothes', $laundry->id);

        $expected = [$third, $first, $second];
        $this->TodoSubtasks->reorder($expected);
        $results = $this->TodoSubtasks->find()->orderAsc('ranking')->toArray();
        $this->assertSame(count($results), count($expected));
        foreach ($expected as $i => $record) {
            $this->assertEquals($record->id, $results[$i]->id);
        }
    }
}
