<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Feed;
use App\Service\FeedService;
use App\Service\FeedSyncException;
use App\Test\TestCase\FactoryTrait;
use Cake\Http\Client;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validation;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class FeedServiceTest extends TestCase
{
    use FactoryTrait;
    use HttpClientTrait;

    public array $fixtures = [
        'app.FeedItemUsers',
        'app.FeedItems',
        'app.FeedSubscriptions',
        'app.FeedCategories',
        'app.Feeds',
        'app.Users',
    ];

    private HtmlSanitizerInterface $cleaner;

    public function setUp(): void
    {
        parent::setUp();
        $config = new HtmlSanitizerConfig();
        $config = $config->allowSafeElements();

        $this->cleaner = new HtmlSanitizer($config);
    }

    public function feedItemCount(Feed $feed): int
    {
        $feedItems = $this->fetchTable('FeedItems');

        return $feedItems->find()->where(['FeedItems.feed_id' => $feed->id])->count();
    }

    public function testRefreshFeedEmptyResponse()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);

        // Empty response
        $res = $this->newClientResponse();
        $this->mockClientGet($url, $res);
        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        $this->assertEquals(0, $this->feedItemCount($feed));
    }

    public function testRefreshFeedUnknownContentType()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);

        // Junk response
        $res = $this->newClientResponse(200, ['Content-Type: lolnope'], 'some junk');
        $this->mockClientGet($url, $res);

        $service = new FeedService($client, $this->cleaner);
        $this->expectException(FeedSyncException::class);
        $service->refreshFeed($feed);
    }

    public function testRefreshFeedEmptyBody()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);

        // Junk response
        $res = $this->newClientResponse(200, ['Content-Type: application/rss+xml; charset=UTF-8'], '');
        $this->mockClientGet($url, $res);

        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        $this->assertEquals(0, $this->feedItemCount($feed));
    }

    public function testRefreshFeedFollowRedirect()
    {
        $client = new Client();
        $url = 'https://example.org/feed';
        $feed = $this->makeFeed($url);

        // Redirect
        $res = $this->newClientResponse(
            302,
            ['Location: https://example.org/rss'],
            ''
        );
        $this->mockClientGet('https://example.org/feed', $res);

        // Simple RSS
        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/rss'],
            $this->readFeedFixture('mark-story-com.rss')
        );
        $this->mockClientGet('https://example.org/rss', $res);

        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        $this->assertEquals(20, $this->feedItemCount($feed));
    }

    public function testRefreshFeedSuccessSimpleRss()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);

        // Simple RSS
        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/rss'],
            $this->readFeedFixture('mark-story-com.rss')
        );
        $this->mockClientGet($url, $res);
        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        $this->assertEquals(20, $this->feedItemCount($feed));
        $feeditems = $this->fetchTable('FeedItems');
        $item = $feeditems->find()->firstOrFail();

        $this->assertNotEmpty($item->guid);
        $this->assertTrue(Validation::url($item->url));
        $this->assertNotEmpty($item->title);
        $this->assertNotEmpty($item->summary);
        $this->assertNotEmpty($item->author);
        $this->assertNotEmpty($item->published_at);
        $this->assertEquals($feed->id, $item->feed_id);
    }

    public function testRefreshFeedSuccessSimpleAtom()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);

        // Simple RSS
        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/atom+xml'],
            $this->readFeedFixture('github-releases.atom')
        );
        $this->mockClientGet($url, $res);
        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        $this->assertEquals(10, $this->feedItemCount($feed));
        $feeditems = $this->fetchTable('FeedItems');
        $item = $feeditems->find()->firstOrFail();

        $this->assertNotEmpty($item->guid);
        $this->assertTrue(Validation::url($item->url));
        $this->assertNotEmpty($item->title);
        $this->assertNotEmpty($item->author);
        $this->assertNotEmpty($item->summary);
        $this->assertStringNotContainsString('&gt;', $item->content);
        $this->assertStringContainsString('<ul>', $item->content);
        $this->assertNotEmpty($item->published_at);
        $this->assertEquals($feed->id, $item->feed_id);
    }

    public function testRefreshFeedUpdateExisting()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);
        $item = $this->makeFeedItem($feed->id, [
            'guid' => 'https://mark-story.com/posts/view/' .
                'server-rendered-components-with-template-fragments-and-webcomponents?utm_source=rss',
            'url' => 'https://mark-story.com/wrong',
            'title' => 'replace me',
            'summary' => 'replace me',
        ]);

        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/rss'],
            $this->readFeedFixture('mark-story-com.rss')
        );
        $this->mockClientGet($url, $res);
        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        $this->assertEquals(20, $this->feedItemCount($feed));
        $feeditems = $this->fetchTable('FeedItems');
        $refresh = $feeditems->findById($item->id)->firstOrFail();

        $this->assertEquals($item->guid, $refresh->guid);
        $this->assertTrue(Validation::url($refresh->url));
        $this->assertNotEquals('replace me', $refresh->title);
        $this->assertNotEquals('replace me', $refresh->summary);
        $this->assertNotEmpty($refresh->published_at);
        $this->assertEquals($feed->id, $refresh->feed_id);
    }

    public function testRefreshFeedUpdateUnreadCount()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $category = $this->makeFeedCategory('Blogs');
        $otherCategory = $this->makeFeedCategory('Blogs', 2);
        $feed = $this->makeFeed($url);
        $sub = $this->makeFeedSubscription($category->id, $feed->id);
        $otherSub = $this->makeFeedSubscription($otherCategory->id, $feed->id, 2);

        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/rss'],
            $this->readFeedFixture('mark-story-com.rss')
        );
        $this->mockClientGet($url, $res);

        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        /** @var \App\Model\Table\FeedSubscriptionsTable $subs */
        $subs = $this->fetchTable('FeedSubscriptions');

        $refresh = $subs->get($sub->id);
        $this->assertEquals(20, $refresh->unread_item_count);

        $refresh = $subs->get($otherSub->id);
        $this->assertEquals(20, $refresh->unread_item_count);

        /** @var \App\Model\Table\FeedCategoriesTable $categories */
        $categories = $this->fetchTable('FeedCategories');
        $refresh = $categories->get($category->id);
        $this->assertEquals(20, $refresh->unread_item_count);

        $refresh = $categories->get($otherCategory->id);
        $this->assertEquals(20, $refresh->unread_item_count);
    }

    public function testRefreshFeedUpdateUnreadCountExistingState()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed($url);
        $sub = $this->makeFeedSubscription($category->id, $feed->id);
        $item = $this->makeFeedItem($feed->id, [
            'guid' => 'https://mark-story.com/posts/view/' .
                'server-rendered-components-with-template-fragments-and-webcomponents?utm_source=rss',
            'url' => 'https://mark-story.com/wrong',
            'title' => 'replace me',
            'summary' => 'replace me',
        ]);
        $this->makeFeedItemUser($item->id, 1);

        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/rss'],
            $this->readFeedFixture('mark-story-com.rss')
        );
        $this->mockClientGet($url, $res);

        $service = new FeedService($client, $this->cleaner);
        $service->refreshFeed($feed);

        /** @var \App\Model\Table\FeedSubscriptionsTable $subs */
        $subs = $this->fetchTable('FeedSubscriptions');

        $refresh = $subs->get($sub->id);
        $this->assertEquals(19, $refresh->unread_item_count, 'Should account for existing read state');

        /** @var \App\Model\Table\FeedCategoriesTable $categories */
        $categories = $this->fetchTable('FeedCategories');
        $refresh = $categories->get($category->id);
        $this->assertEquals(19, $refresh->unread_item_count, 'Should account for existing read state');
    }

    public function testDiscoverFollowRedirect(): void
    {
        $client = new Client();

        // Simulate a http -> https redirect
        $res = $this->newClientResponse(
            302,
            ['Location: https://example.org'],
            $this->readFeedFixture('mark-story-com.html')
        );
        $this->mockClientGet('http://example.org', $res);

        $res = $this->newClientResponse(
            200,
            ['Content-Type: text/html'],
            $this->readFeedFixture('mark-story-com.html')
        );
        $this->mockClientGet('https://example.org', $res);

        $service = new FeedService($client, $this->cleaner);
        $feeds = $service->discoverFeeds('http://example.org');
        $this->assertCount(1, $feeds);
        $feed = $feeds[0];
        $this->assertInstanceOf(Feed::class, $feed);
        // TODO these should be https ideally.
        $this->assertEquals('http://example.org/posts/archive.rss', $feed->url);
        $this->assertEquals('Posts | Mark Story', $feed->default_alias);
        $this->assertEquals('http://example.org/favicon.png', $feed->favicon_url);
    }

    public function testDiscoverFeedRss(): void
    {
        $client = new Client();
        $url = 'https://example.org';
        $res = $this->newClientResponse(
            200,
            ['Content-Type: text/html'],
            $this->readFeedFixture('mark-story-com.html')
        );
        $this->mockClientGet($url, $res);
        $service = new FeedService($client, $this->cleaner);
        $feeds = $service->discoverFeeds($url);
        $this->assertCount(1, $feeds);
        $feed = $feeds[0];
        $this->assertInstanceOf(Feed::class, $feed);
        $this->assertEquals('https://example.org/posts/archive.rss', $feed->url);
        $this->assertEquals('Posts | Mark Story', $feed->default_alias);
        $this->assertEquals('https://example.org/favicon.png', $feed->favicon_url);
    }

    public function testDiscoverFeedAtom(): void
    {
        $client = new Client();
        $url = 'https://example.org';
        $res = $this->newClientResponse(
            200,
            ['Content-Type: text/html'],
            $this->readFeedFixture('github-releases.html')
        );
        $this->mockClientGet($url, $res);
        $service = new FeedService($client, $this->cleaner);
        $feeds = $service->discoverFeeds($url);
        $this->assertCount(2, $feeds);
        $feed = $feeds[0];
        $this->assertInstanceOf(Feed::class, $feed);
        $this->assertEquals('Releases - markstory/asset_compress', $feed->default_alias);
        $this->assertEquals('https://github.com/markstory/asset_compress/releases.atom', $feed->url);
    }
}
