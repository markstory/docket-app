<?php
declare(strict_types=1);

namespace Calendar\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CalendarItemsFixture
 */
class CalendarItemsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'calendar_source_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'provider_id' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'title' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null,  'comment' => '', 'precision' => null],
        'start_date' => ['type' => 'date', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
        'start_time' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
        'end_date' => ['type' => 'date', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
        'end_time' => ['type' => 'datetime', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
        'all_day' => ['type' => 'boolean', 'default' => false, 'null' => false],
        'html_link' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'timestamp', 'length' => null, 'precision' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => ''],
        'modified' => ['type' => 'timestamp', 'length' => null, 'precision' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => ''],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'uniq_calendar_source_id' => ['type' => 'unique', 'columns' => ['calendar_source_id', 'provider_id'], 'length' => []],
            'calendar_items_ibfk_1' => ['type' => 'foreign', 'columns' => ['calendar_source_id'], 'references' => ['calendar_sources', 'id'], 'update' => 'noAction', 'delete' => 'noAction', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_0900_ai_ci'
        ],
    ];
    // phpcs:enable
}
