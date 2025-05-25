<?php
declare(strict_types=1);

namespace Feeds\Test\TestCase\Command;

use App\Test\TestCase\FactoryTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\FeedSyncCommand Test Case
 */
class FeedSyncCommandTest extends TestCase
{
    use HttpClientTrait;
    use ConsoleIntegrationTestTrait;
    use FactoryTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'plugin.Feeds.FeedSubscriptions',
        'plugin.Feeds.Feeds',
        'app.Users',
        'plugin.Feeds.FeedCategories',
        'plugin.Feeds.SavedFeedItems',
        'plugin.Feeds.FeedItems',
        'plugin.Feeds.FeedSubscriptionsFeedItems',
    ];

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed');
        $this->makeFeedSubscription($category->id, $feed->id);

        $url = 'https://example.com/feed';
        $res = $this->newClientResponse(
            200,
            ['Content-Type: application/rss+xml'],
            $this->readFeedFixture('mark-story-com.rss')
        );
        $this->mockClientGet($url, $res);

        $this->exec('feed_sync --verbose');
        $this->assertExitSuccess();
        $this->assertOutputContains('Sync start');
        $this->assertOutputContains('Sync complete');
        $this->assertOutputContains("Sync {$feed->url} start");
        $this->assertOutputContains("Sync {$feed->url} complete");

        /** @var \Feeds\Model\Entity\Feed $refresh */
        $refresh = $this->fetchTable('Feeds.Feeds')->get($feed->id);
        $this->assertNotEmpty($refresh->last_refresh);
        $this->assertNotEquals($refresh->last_refresh, $feed->last_refresh);

        $itemCount = $this->fetchTable('Feeds.FeedItems')->find()->count();
        $this->assertGreaterThan(2, $itemCount);
    }

    public function testExecuteNoSubscription(): void
    {
        $feed = $this->makeFeed('https://example.com/feed');

        $this->exec('feed_sync --verbose');
        $this->assertExitSuccess();
        $this->assertOutputContains('Sync start');
        $this->assertOutputContains('Sync complete');
        $this->assertOutputNotContains("Sync {$feed->url} start");
        $this->assertOutputNotContains("Sync {$feed->url} end");

        /** @var \Feeds\Model\Entity\Feed $refresh */
        $refresh = $this->fetchTable('Feeds.Feeds')->get($feed->id);
        $this->assertEmpty($refresh->last_refresh);

        $itemCount = $this->fetchTable('Feeds.FeedItems')->find()->count();
        $this->assertEquals(0, $itemCount);
    }

    public function testExecuteServerError(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed');
        $this->makeFeedSubscription($category->id, $feed->id);

        $url = 'https://example.com/feed';
        $res = $this->newClientResponse(
            500,
            ['Content-Type: text/html'],
            'not good',
        );
        $this->mockClientGet($url, $res);

        $this->exec('feed_sync --verbose');
        $this->assertExitSuccess();
        $this->assertOutputContains('Sync start');
        $this->assertOutputContains('Sync complete');
        $this->assertOutputContains("Sync {$feed->url} start");
        $this->assertErrorContains("Sync for {$feed->url} failed");

        /** @var \Feeds\Model\Entity\Feed $refresh */
        $refresh = $this->fetchTable('Feeds.Feeds')->get($feed->id);
        $this->assertEmpty($refresh->last_refresh);
        $this->assertEquals($refresh->last_refresh, $feed->last_refresh);
    }
}
