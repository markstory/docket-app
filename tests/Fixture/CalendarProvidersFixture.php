<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CalendarProvidersFixture
 */
class CalendarProvidersFixture extends TestFixture
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
                'kind' => 'Lorem ipsum dolor sit amet',
                'identifier' => 'Lorem ipsum dolor sit amet',
                'access_token' => 'Lorem ipsum dolor sit amet',
                'refresh_token' => 'Lorem ipsum dolor sit amet',
                'token_expiry' => '2021-07-09 03:06:37',
            ],
        ];
        parent::init();
    }
}
