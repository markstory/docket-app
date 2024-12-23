<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SavedFeedItemsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SavedFeedItemsTable Test Case
 */
class SavedFeedItemsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SavedFeedItemsTable
     */
    protected $SavedFeedItems;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.SavedFeedItems',
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
        $config = $this->getTableLocator()->exists('SavedFeedItems') ? [] : ['className' => SavedFeedItemsTable::class];
        $this->SavedFeedItems = $this->getTableLocator()->get('SavedFeedItems', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->SavedFeedItems);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\SavedFeedItemsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\SavedFeedItemsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
