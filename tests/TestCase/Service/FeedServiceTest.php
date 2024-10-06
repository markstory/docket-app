<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\FeedService;
use App\Test\TestCase\FactoryTrait;
use Cake\Core\Container;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use RuntimeException;

class FeedServiceTest extends TestCase
{
    use FactoryTrait;

    public array $fixtures = [
        'app.Users',
        'app.Feeds',
        'app.FeedItems',
    ];

    public function testRefreshFeedUnknownContentType()
    {
    }

    public function testRefreshFeedEmptyBody()
    {
    }

    public function testRefreshFeedSuccess()
    {
    }
}
