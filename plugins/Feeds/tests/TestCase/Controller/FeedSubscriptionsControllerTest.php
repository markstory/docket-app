<?php
declare(strict_types=1);

namespace Feeds\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Feeds\Model\Table\FeedsTable;
use Feeds\Model\Table\FeedSubscriptionsTable;

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
        'app.SavedFeedItems',
        'app.FeedSubscriptionsFeedItems',
        'app.FeedItemUsers',
        'app.FeedItems',
        'app.FeedSubscriptions',
        'app.Feeds',
        'app.FeedCategories',
        'app.Users',
    ];

    private FeedsTable $Feeds;

    public function setUp(): void
    {
        parent::setUp();
        /** @var \Feeds\Model\Table\FeedsTable $this->Feeds */
        $this->Feeds = $this->fetchTable('Feeds.Feeds');
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::index()
     */
    public function testIndex(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);
        $otherfeed = $this->makeFeed('https://example.com/feed2.xml');
        $other = $this->makeFeedSubscription($category->id, $otherfeed->id, 1, ['alias' => 'other feed']);

        $this->login();
        $this->get('/feeds/list');
        $this->assertResponseOk();

        $this->assertResponseContains($subscription->alias);
        $this->assertResponseContains($other->alias);
    }

    /**
     * Test home method
     *
     * @return void
     */
    public function testHome(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $item = $this->makeFeedItem($feed->id, ['title' => 'feed1 first post']);
        $subscription = $this->makeFeedSubscription($category->id, $feed->id, 1);

        $otherfeed = $this->makeFeed('https://example.com/feed2.xml');
        $otherItem = $this->makeFeedItem($feed->id, ['title' => 'feed2 first post']);
        $this->makeFeedSubscription($category->id, $otherfeed->id, 1, ['alias' => 'other feed']);

        $this->login();
        $this->get('/feeds');
        $this->assertResponseOk();

        $this->assertResponseContains($category->title);
        $this->assertResponseContains($subscription->alias);
        $this->assertResponseContains($item->title);
        $this->assertResponseContains($otherItem->title);
    }

    /**
     * Test home method
     *
     * @return void
     */
    public function testHomeNoData(): void
    {
        $this->login();
        $this->get('/feeds');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::view()
     */
    public function testView(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);
        $item = $this->makeFeedItem($feed->id);

        $this->login();
        $this->get("/feeds/{$feed->id}/view");
        $this->assertResponseOk();
        $this->assertResponseContains($subscription->alias);
        $this->assertResponseContains($item->title);
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
     * Test add get
     *
     * @return void
     * @uses \App\Controller\FeedSubscriptionsController::add()
     */
    public function testAddGet(): void
    {
        $this->makeFeedCategory('Blogs');

        $this->login();
        $this->enableCsrfToken();
        $this->get('/feeds/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Blogs');
        $this->assertResponseContains('Add Feed');
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
        $feedItemUsers = $this->fetchTable('Feeds.FeedItemUsers');
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
        $subscription = $this->makeFeedSubscription($category->id, $feed->id, 1, ['unread_item_count' => 2]);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);
        $otherItem = $this->makeFeedItem($feed->id, ['title' => 'also']);
        $notIncluded = $this->makeFeedItem($feed->id, ['title' => 'nope']);

        $this->login();
        $this->enableCsrfToken();
        $data = [
            'id' => [$item->id, $otherItem->id],
        ];
        $this->post('/feeds/items/mark-read', $data);
        $this->assertResponseCode(302);
        $this->assertRedirect('/');

        $feedItemUsers = $this->fetchTable('Feeds.FeedItemUsers');
        $state = $feedItemUsers->findByFeedItemId($item->id)->first();
        $this->assertNotEmpty($state);

        $state = $feedItemUsers->findByFeedItemId($otherItem->id)->first();
        $this->assertNotEmpty($state);

        // No state for item not included.
        $state = $feedItemUsers->findByFeedItemId($notIncluded->id)->first();
        $this->assertNull($state);

        $sub = $this->fetchTable('Feeds.FeedSubscriptions')->get(
            $subscription->id,
            contain: FeedSubscriptionsTable::VIEW_CONTAIN
        );
        $this->assertEquals(1, $sub->unread_item_count);
    }

    public function testMarkItemsReadPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 1);
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $this->makeFeedSubscription($category->id, $feed->id, 1);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);

        $otherCategory = $this->makeFeedCategory('News', 2);
        $otherFeed = $this->makeFeed('https://example.org/feed.xml');
        $this->makeFeedSubscription($otherCategory->id, $otherFeed->id, 2);
        $otherItem = $this->makeFeedItem($otherFeed->id, ['title' => 'derp']);

        $this->login();
        $this->enableCsrfToken();
        $data = [
            'id' => [$item->id, $otherItem->id],
        ];
        $this->post('/feeds/items/mark-read', $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('Invalid records');
    }

    public function testMarkItemsNone(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $data = [];
        $this->post('/feeds/items/mark-read', $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('required parameter');
    }

    public function testMarkItemsTooMany(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $data = [
            'id' => array_fill(0, 101, 123),
        ];
        $this->post('/feeds/items/mark-read', $data);
        $this->assertResponseCode(400);
        $this->assertResponseContains('Too many ids');
    }

    public function testReadVisit(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id, 1, ['unread_item_count' => 1]);
        $item = $this->makeFeedItem($feed->id, ['title' => 'yes']);
        $this->login();
        $this->get("/feeds/{$subscription->id}/read-visit/{$item->id}");

        $this->assertRedirect($item->url);
        $feedItemUsers = $this->fetchTable('Feeds.FeedItemUsers');
        $state = $feedItemUsers->findByFeedItemId($item->id)->firstOrFail();
        $this->assertNotEmpty($state);
        $this->assertEquals($state->user_id, $category->user_id);

        $sub = $this->fetchTable('Feeds.FeedSubscriptions')->get(
            $subscription->id,
            contain: FeedSubscriptionsTable::VIEW_CONTAIN
        );
        $this->assertEquals(0, $sub->unread_item_count);
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
     * Test edit get
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $otherCategory = $this->makeFeedCategory('Film');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/feeds/{$subscription->id}/edit");

        $this->assertResponseOk();
        $this->assertResponseContains($subscription->alias);
        $this->assertResponseContains($category->title);
        $this->assertResponseContains($otherCategory->title);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $otherCategory = $this->makeFeedCategory('Film');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/feeds/{$subscription->id}/edit", [
            'feed_category_id' => $otherCategory->id,
            'alias' => 'updated alias',
            'ranking' => 1,
        ]);
        $this->assertRedirect("/feeds/{$subscription->id}/view");
        $subscriptions = $this->fetchTable('Feeds.FeedSubscriptions');
        $refresh = $subscriptions->get($subscription->id);
        $this->assertEquals($refresh->alias, 'updated alias');
        $this->assertEquals($refresh->feed_category_id, $otherCategory->id);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEditUrl(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/feeds/{$subscription->id}/edit", [
            'url' => 'https://example.com/feed.rss',
            'alias' => 'updated alias',
            'ranking' => 1,
        ]);
        $this->assertRedirect("/feeds/{$subscription->id}/view");
        $subscriptions = $this->fetchTable('Feeds.FeedSubscriptions');
        $refresh = $subscriptions->get($subscription->id);
        $this->assertEquals($refresh->alias, 'updated alias');

        $this->assertNotEquals($refresh->feed_id, $feed->id, 'feed url should change');
        $feeds = $this->fetchTable('Feeds.Feeds');
        $refreshFeed = $feeds->get($refresh->feed_id);
        $this->assertEquals('https://example.com/feed.rss', $refreshFeed->url);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeleteUpdateCounters(): void
    {
        $category = $this->makeFeedCategory('Blogs', 1, ['unread_item_count' => 2]);
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/feeds/{$subscription->id}/delete");

        $this->assertRedirect(['_name' => 'feedsubscriptions:index']);
        $refresh = $this->fetchTable('Feeds.FeedCategories')->get($category->id);
        $this->assertEquals(0, $refresh->unread_item_count);
    }

    /**
     * Test delete permissions
     *
     * @return void
     */
    public function testDeletePermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login(2);
        $this->enableCsrfToken();
        $this->post("/feeds/{$subscription->id}/delete");

        $this->assertResponseCode(403);
    }

    public function testDeleteConfirm(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id);

        $this->login();
        $this->get("/feeds/{$subscription->id}/delete/confirm");

        $this->assertResponseOk();
        $this->assertResponseContains('Are you sure?');

        $subs = $this->fetchTable('Feeds.FeedSubscriptions');
        $alive = $subs->get($subscription->id);
        $this->assertNotEmpty($alive);
    }

    public function testDeleteConfirmPermissions(): void
    {
        $category = $this->makeFeedCategory('Blogs', 2);
        $feed = $this->makeFeed('https://example.com/feed.xml');
        $subscription = $this->makeFeedSubscription($category->id, $feed->id, 2);

        $this->login(1);
        $this->get("/feeds/{$subscription->id}/delete/confirm");

        $this->assertResponseCode(403);
        $subs = $this->fetchTable('Feeds.FeedSubscriptions');
        $alive = $subs->get($subscription->id);
        $this->assertNotEmpty($alive);
    }
}
