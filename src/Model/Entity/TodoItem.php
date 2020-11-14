<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * TodoItem Entity
 *
 * @property int $id
 * @property int $project_id
 * @property string|null $title
 * @property string|null $body
 * @property \Cake\I18n\FrozenDate|null $due_on
 * @property bool $completed
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Project $project
 * @property \App\Model\Entity\TodoComment[] $todo_comments
 * @property \App\Model\Entity\TodoSubtask[] $todo_subtasks
 * @property \App\Model\Entity\TodoLabel[] $todo_labels
 */
class TodoItem extends Entity
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
        'project_id' => true,
        'title' => true,
        'body' => true,
        'due_on' => true,
        'completed' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'todo_comments' => true,
        'todo_subtasks' => true,
        'todo_labels' => true,
    ];

    public function complete(): void
    {
        $this->completed = true;
        $this->due_on = new FrozenTime();
    }
}
