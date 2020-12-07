<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TodoSubtask Entity
 *
 * @property int $id
 * @property int $todo_item_id
 * @property string|null $title
 * @property string|null $body
 * @property int $ranking
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\TodoItem $todo_item
 */
class TodoSubtask extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'todo_item_id' => true,
        'title' => true,
        'body' => true,
        'ranking' => true,
        'created' => true,
        'modified' => true,
        'todo_item' => true,
    ];
}
