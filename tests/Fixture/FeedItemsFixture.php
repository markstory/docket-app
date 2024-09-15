<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FeedItemsFixture
 */
class FeedItemsFixture extends TestFixture
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
                'title' => 'Lorem ipsum dolor sit amet',
                'summary' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'published_at' => '2024-09-15 03:50:19',
                'thumbnail_image_url' => 'Lorem ipsum dolor sit amet',
                'created' => 1726372219,
                'modified' => 1726372219,
            ],
        ];
        parent::init();
    }
}
