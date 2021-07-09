<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CalendarItemsFixture
 */
class CalendarItemsFixture extends TestFixture
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
                'calendar_source_id' => 1,
                'provider_id' => 'Lorem ipsum dolor sit amet',
                'title' => 'Lorem ipsum dolor sit amet',
                'start_time' => '2021-07-09 03:06:43',
                'end_time' => '2021-07-09 03:06:43',
                'html_link' => 'Lorem ipsum dolor sit amet',
                'created' => 1625800003,
                'modified' => 1625800003,
            ],
        ];
        parent::init();
    }
}
