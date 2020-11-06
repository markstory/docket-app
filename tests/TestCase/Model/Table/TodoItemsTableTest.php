<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TodoItemsTable;
use Cake\TestSuite\TestCase;

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
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
