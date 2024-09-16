<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\FeedSubscriptionsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FeedSubscriptionsController Test Case
 *
 * @uses \App\Controller\FeedSubscriptionsController
 */
class FeedSubscriptionsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.FeedSubscriptions',
        'app.Feeds',
        'app.Users',
        'app.FeedCategories',
        'app.SavedFeedItems',
        'app.FeedItems',
        'app.FeedSubscriptionsFeedItems',
    ];

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
