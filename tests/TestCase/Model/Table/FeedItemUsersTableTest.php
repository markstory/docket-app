<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FeedItemUsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FeedItemUsersTable Test Case
 */
class FeedItemUsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FeedItemUsersTable
     */
    protected $FeedItemUsers;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.FeedItemUsers',
        'app.Users',
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
        $config = $this->getTableLocator()->exists('FeedItemUsers') ? [] : ['className' => FeedItemUsersTable::class];
        $this->FeedItemUsers = $this->getTableLocator()->get('FeedItemUsers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->FeedItemUsers);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\FeedItemUsersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\FeedItemUsersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
