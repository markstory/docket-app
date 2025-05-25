<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

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
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Tasks\Model\Entity\Task $todo_item
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
    protected array $_accessible = [
        'task_id' => true,
        'title' => true,
        'body' => true,
        'ranking' => true,
        'completed' => true,
        'created' => false,
        'modified' => false,
        'task' => true,
    ];

    public function toggle(): void
    {
        $this->completed = !$this->completed;
    }
}
