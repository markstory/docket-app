<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeedSubscriptionsFeedItemsFixture
 */
class FeedSubscriptionsFeedItemsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'feed_subscription_id' => 1,
                'feed_item_id' => 1,
                'is_read' => 1,
                'is_saved' => 1,
                'is_hidden' => 1,
                'created' => 1726372256,
                'modified' => 1726372256,
            ],
        ];
        parent::init();
    }
}
