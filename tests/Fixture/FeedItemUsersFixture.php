<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeedItemUsersFixture
 */
class FeedItemUsersFixture extends TestFixture
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
                'user_id' => 1,
                'feed_item_id' => 1,
                'read_at' => '2024-11-04 03:53:17',
                'saved_at' => '2024-11-04 03:53:17',
            ],
        ];
        parent::init();
    }
}
