<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\FeedSyncCommand;
use App\Test\TestCase\FactoryTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\FeedSyncCommand Test Case
 *
 * @uses \App\Command\FeedSyncCommand
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
        'app.FeedSubscriptions',
        'app.Feeds',
        'app.Users',
        'app.FeedCategories',
        'app.SavedFeedItems',
        'app.FeedItems',
        'app.FeedSubscriptionsFeedItems',
    ];

    /**
     * Test execute method
     *
     * @return void
     * @uses \App\Command\FeedSyncCommand::execute()
     */
    public function testExecute(): void
    {
        $category = $this->makeFeedCategory('Blogs');
        $feed = $this->makeFeed('https://example.com/feed');
        $subOne = $this->makeFeedSubscription($category->id, $feed->id);

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
        $this->assertOutputContains("Sync {$feed->url} end");

        /** @var \App\Model\Entity\Feed $refresh */
        $refresh = $this->fetchTable('Feeds')->get($feed->id);
        $this->assertNotEmpty($refresh->last_refresh);
        $this->assertNotEquals($refresh->last_refresh, $feed->last_refresh);
    }
}
