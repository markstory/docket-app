<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TodoItemsTodoLabelsFixture
 */
class TodoItemsTodoLabelsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // phpcs:disable
    public $fields = [
        'todo_item_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'todo_label_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        '_indexes' => [
            'todo_label_id' => ['type' => 'index', 'columns' => ['todo_label_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['todo_item_id', 'todo_label_id'], 'length' => []],
            'todo_items_todo_labels_ibfk_2' => ['type' => 'foreign', 'columns' => ['todo_label_id'], 'references' => ['todo_labels', 'id'], 'update' => 'restrict', 'delete' => 'cascade', 'length' => []],
            'todo_items_todo_labels_ibfk_1' => ['type' => 'foreign', 'columns' => ['todo_item_id'], 'references' => ['todo_items', 'id'], 'update' => 'restrict', 'delete' => 'cascade', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'latin1_swedish_ci'
        ],
    ];
    // phpcs:enable
}
