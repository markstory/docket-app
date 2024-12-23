<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FeedItemsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FeedItemsTable Test Case
 */
class FeedItemsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FeedItemsTable
     */
    protected $FeedItems;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.FeedItems',
        'app.Feeds',
        'app.FeedSubscriptions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('FeedItems') ? [] : ['className' => FeedItemsTable::class];
        $this->FeedItems = $this->getTableLocator()->get('FeedItems', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->FeedItems);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\FeedItemsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\FeedItemsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
