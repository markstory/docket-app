<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TodoSubtasksTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TodoSubtasksTable Test Case
 */
class TodoSubtasksTableTest extends TestCase
{
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
