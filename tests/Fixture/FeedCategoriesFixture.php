<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeedCategoriesFixture
 */
class FeedCategoriesFixture extends TestFixture
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
                'title' => 'Lorem ipsum dolor sit amet',
                'color' => 1,
                'ranking' => 1,
                'created' => 1726372228,
                'modified' => 1726372228,
            ],
        ];
        parent::init();
    }
}
