<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeedsFixture
 */
class FeedsFixture extends TestFixture
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
                'url' => 'Lorem ipsum dolor sit amet',
                'refresh_interval' => 1,
                'last_refresh' => '2024-09-15 03:50:15',
                'created' => 1726372215,
                'modified' => 1726372215,
            ],
        ];
        parent::init();
    }
}
