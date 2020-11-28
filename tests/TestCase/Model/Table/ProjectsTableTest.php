<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsTable;
use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsTable Test Case
 */
class ProjectsTableTest extends TestCase
{
    use FactoryTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsTable
     */
    protected $Projects;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Projects',
        'app.Users',
        'app.TodoItems',
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
        $config = $this->getTableLocator()->exists('Projects') ? [] : ['className' => ProjectsTable::class];
        $this->Projects = $this->getTableLocator()->get('Projects', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Projects);

        parent::tearDown();
    }

    public function testReorder()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);

        $expected = [$fun, $home, $work];
        $this->Projects->reorder($expected);
        $results = $this->Projects->find()->orderAsc('ranking')->toArray();
        $this->assertSame(count($results), count($expected));
        foreach ($expected as $i => $record) {
            $this->assertEquals($record->id, $results[$i]->id);
        }
    }
}
