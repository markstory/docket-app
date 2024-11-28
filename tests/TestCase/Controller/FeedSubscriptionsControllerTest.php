<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Table\FeedsTable;
use App\Test\TestCase\FactoryTrait;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FeedSubscriptionsController Test Case
 *
 * @uses \App\Controller\FeedSubscriptionsController
 */
class FeedSubscriptionsControllerTest extends TestCase
{
    use HttpClientTrait;
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

    public function testDiscoverPostSuccess(): void
    {
        $url = 'https://example.org';
        $res = $this->newClientResponse(
            200,
            ['Content-Type: text/html'],
            $this->readFeedFixture('mark-story-com.html')
        );
        $this->mockClientGet($url, $res);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/feeds/discover', [
            'url' => 'https://example.org',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Mark Story');
        $this->assertResponseContains('https://example.org/posts/archive.rss');
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
            'favicon_url' => 'https://example.com/favicon.ico',
        ]);
        $this->assertFlashMessage('Feed subscription added');
        $this->assertRedirect('/feeds');

        $feedCount = $this->Feeds->findByUrl($feed->url)->count();
        $this->assertEquals(1, $feedCount);

        $sub = $this->Feeds->FeedSubscriptions->findByFeedId($feed->id)->firstOrFail();
        $this->assertNotEmpty($sub);
        $this->assertEquals(1, $sub->user_id);
        $this->assertEquals('Example site', $sub->alias);
        $refresh = $this->Feeds->get($feed->id);
        $this->assertEquals('https://example.com/favicon.ico', $refresh->favicon_url);
    }

    /**
     * Test that viewItem goes
     */
    public function testViewItem(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $item = $this->makeFeedItem($feed->id, ['title' => 'derpity']);
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/feeds/{$subscription->id}/items/{$item->id}");
        $this->assertResponseOk();

        // Read at state recorded
        $feedItemUsers = $this->fetchTable('FeedItemUsers');
        $state = $feedItemUsers->findByUserId(1)->firstOrFail();
        $this->assertNotEmpty($state->read_at);
        $this->assertEquals($state->feed_item_id, $item->id);
    }

    public function testViewItemPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 2);
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id, 2);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/feeds/{$subscription->id}/items/{$item->id}");
        $this->assertResponseCode(403);
    }

    public function testMarkItemsRead(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);
        $otherItem = $this->makeFeedItem($feed->id, ['title' => 'also']);
        $notIncluded = $this->makeFeedItem($feed->id, ['title' => 'nope']);

        $this->login();
        $this->enableCsrfToken();
        $data = [
            'id' => [$item->id, $otherItem->id],
        ];
        $this->post("/feeds/{$subscription->id}/items/mark-read", $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        $feedItemUsers = $this->fetchTable('FeedItemUsers');
        $state = $feedItemUsers->findByFeedItemId($item->id)->first();
        $this->assertNotEmpty($state);

        $state = $feedItemUsers->findByFeedItemId($otherItem->id)->first();
        $this->assertNotEmpty($state);

        // No state for item not included.
        $state = $feedItemUsers->findByFeedItemId($notIncluded->id)->first();
        $this->assertNull($state);
    }

    public function testMarkItemsReadPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);

        $otherFeed = $this->makeFeed('https://example.org/feed.xml');
        $this->makeFeedSubscription($category->id, $otherFeed->id);
        $otherItem = $this->makeFeedItem($otherFeed->id, ['title' => 'derp']);

        $this->login();
        $this->enableCsrfToken();
        $data = [
            'id' => [$item->id, $otherItem->id],
        ];
        $this->post("/feeds/{$subscription->id}/items/mark-read", $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('Invalid records');
    }

    public function testMarkItemsNone(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $data = [];
        $this->post("/feeds/{$subscription->id}/items/mark-read", $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('required parameter');
    }

    public function testMarkItemsTooMany(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $data = [
            'id' => array_fill(0, 101, 123),
        ];
        $this->post("/feeds/{$subscription->id}/items/mark-read", $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('Too many ids');
    }

    public function testReadVisit(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);
        $this->login();
        $this->get("/feeds/{$subscription->id}/read-visit/{$item->id}");

        $this->assertRedirect($item->url);
        $feedItemUsers = $this->fetchTable('FeedItemUsers');
        $state = $feedItemUsers->findByFeedItemId($item->id)->firstOrFail();
        $this->assertNotEmpty($state);
        $this->assertEquals($state->user_id, $category->user_id);
    }

    public function testReadVisitPermission(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);
        // Login as other user
        $this->login(2);
        $this->get("/feeds/{$subscription->id}/read-visit/{$item->id}");

        $this->assertResponseCode(403);
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
