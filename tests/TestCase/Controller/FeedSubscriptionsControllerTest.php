<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Table\FeedsTable;
use App\Test\TestCase\FactoryTrait;
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
    use FactoryTrait;

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

    private FeedsTable $Feeds;

    public function setUp(): void
    {
        parent::setUp();
        $this->Feeds = $this->fetchTable('Feeds');
    }

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
    public function testAddSuccess(): void
    {
        $category = $this->makeFeedCategory('Blogs');

        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/add', [
            'url' => 'https://example.com/feed.xml',
            'alias' => 'Example site',
            'feed_category_id' => $category->id,
        ]);
        $this->assertFlashMessage('Feed subscription added');
        $this->assertRedirect('/feeds');

        $feed = $this->Feeds->findByUrl('https://example.com/feed.xml')->firstOrFail();
        $this->assertNotEmpty($feed);

        $sub = $this->Feeds->FeedSubscriptions->findByFeedId($feed->id)->firstOrFail();
        $this->assertNotEmpty($sub);
        $this->assertEquals(1, $sub->user_id);
        $this->assertEquals('Example site', $sub->alias);
    }

    /**
     * Test add with existing feed
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::add()
     */
    public function testAddSuccessExistingFeed(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');

        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/add', [
            'url' => $feed->url,
            'alias' => 'Example site',
            'feed_category_id' => $category->id,
        ]);
        $this->assertFlashMessage('Feed subscription added');
        $this->assertRedirect('/feeds');

        $feedCount = $this->Feeds->findByUrl($feed->url)->count();
        $this->assertEquals(1, $feedCount);

        $sub = $this->Feeds->FeedSubscriptions->findByFeedId($feed->id)->firstOrFail();
        $this->assertNotEmpty($sub);
        $this->assertEquals(1, $sub->user_id);
        $this->assertEquals('Example site', $sub->alias);
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
