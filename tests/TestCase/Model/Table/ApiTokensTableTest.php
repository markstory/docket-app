<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ApiTokensTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ApiTokensTable Test Case
 */
class ApiTokensTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ApiTokensTable
     */
    protected $ApiTokens;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ApiTokens',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ApiTokens') ? [] : ['className' => ApiTokensTable::class];
        $this->ApiTokens = $this->getTableLocator()->get('ApiTokens', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ApiTokens);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ApiTokensTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ApiTokensTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
