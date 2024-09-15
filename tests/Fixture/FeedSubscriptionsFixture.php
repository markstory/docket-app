<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeedSubscriptionsFixture
 */
class FeedSubscriptionsFixture extends TestFixture
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
                'feed_id' => 1,
                'user_id' => 1,
                'feed_category_id' => 1,
                'alias' => 'Lorem ipsum dolor sit amet',
                'ranking' => 1,
                'created' => 1726372236,
                'modified' => 1726372236,
            ],
        ];
        parent::init();
    }
}
