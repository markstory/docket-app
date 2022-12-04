<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Subtask Entity
 *
 * @property int $id
 * @property int $task_id
 * @property string|null $title
 * @property string|null $body
 * @property int $ranking
 * @property bool $completed
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Task $todo_item
 */
class Subtask extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'task_id' => true,
        'title' => true,
        'body' => true,
        'ranking' => true,
        'completed' => true,
        'created' => true,
        'modified' => true,
        'task' => true,
    ];

    public function toggle()
    {
        $this->completed = !$this->completed;
    }
}
