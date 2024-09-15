<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FeedSubscriptionsFeedItemsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FeedSubscriptionsFeedItemsTable Test Case
 */
class FeedSubscriptionsFeedItemsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FeedSubscriptionsFeedItemsTable
     */
    protected $FeedSubscriptionsFeedItems;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.FeedSubscriptionsFeedItems',
        'app.FeedSubscriptions',
        'app.FeedItems',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('FeedSubscriptionsFeedItems') ? [] : ['className' => FeedSubscriptionsFeedItemsTable::class];
        $this->FeedSubscriptionsFeedItems = $this->getTableLocator()->get('FeedSubscriptionsFeedItems', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->FeedSubscriptionsFeedItems);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\FeedSubscriptionsFeedItemsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\FeedSubscriptionsFeedItemsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
