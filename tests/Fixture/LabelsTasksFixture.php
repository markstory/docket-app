<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * LabelsTasksFixture
 */
class LabelsTasksFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'task_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'label_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_indexes' => [
            'label_id' => ['type' => 'index', 'columns' => ['label_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['task_id', 'label_id'], 'length' => []],
            'labels_tasks_ibfk_2' => ['type' => 'foreign', 'columns' => ['label_id'], 'references' => ['labels', 'id'], 'update' => 'restrict', 'delete' => 'cascade', 'length' => []],
            'labels_tasks_ibfk_1' => ['type' => 'foreign', 'columns' => ['task_id'], 'references' => ['tasks', 'id'], 'update' => 'restrict', 'delete' => 'cascade', 'length' => []],
        ],
    ];
    // phpcs:enable
}
