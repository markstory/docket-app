<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CalendarSourcesFixture
 */
class CalendarSourcesFixture extends TestFixture
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
                'name' => 'Lorem ipsum dolor sit amet',
                'calendar_provider_id' => 1,
                'provider_id' => 'Lorem ipsum dolor sit amet',
                'color' => '',
                'last_sync' => '2021-07-09 03:06:12',
                'sync_token' => 'Lorem ipsum dolor sit amet',
                'created' => 1625799972,
                'modified' => 1625799972,
            ],
        ];
        parent::init();
    }
}
