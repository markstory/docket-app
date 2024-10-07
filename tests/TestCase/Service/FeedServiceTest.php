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
use RuntimeException;

class FeedServiceTest extends TestCase
{
    use FactoryTrait;
    use HttpClientTrait;

    public array $fixtures = [
        'app.Users',
        'app.Feeds',
        'app.FeedItems',
    ];

    public function feedItemCount(Feed $feed): int
    {
        $feedItems = $this->fetchTable('FeedItems');

        return $feedItems->find()->where(['FeedItems.feed_id' => $feed->id])->count();
    }

    public function readFeedFixture(string $fileName): string
    {
        $contents = file_get_contents(TESTS . "Fixture/feeds/$fileName");
        if (!$contents) {
            throw new RuntimeException("Could not read feed fixture $fileName");
        }

        return $contents;
    }

    public function testRefreshFeedEmptyResponse()
    {
        $client = new Client();
        $url = 'https://example.org/rss';
        $feed = $this->makeFeed($url);

        // Empty response
        $res = $this->newClientResponse();
        $this->mockClientGet($url, $res);
        $service = new FeedService($client);
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

        $service = new FeedService($client);
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

        $service = new FeedService($client);
        $service->refreshFeed($feed);

        $this->assertEquals(0, $this->feedItemCount($feed));
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
        $service = new FeedService($client);
        $service->refreshFeed($feed);

        $this->assertEquals(20, $this->feedItemCount($feed));
        $feeditems = $this->fetchTable('FeedItems');
        $item = $feeditems->find()->firstOrFail();

        $this->assertNotEmpty($item->guid);
        $this->assertTrue(Validation::url($item->url));
        $this->assertNotEmpty($item->title);
        $this->assertNotEmpty($item->summary);
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
        $service = new FeedService($client);
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
}
