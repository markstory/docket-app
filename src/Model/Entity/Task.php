<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Exception;

/**
 * Task Entity
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $section_id
 * @property string|null $title
 * @property string|null $body
 * @property \Cake\I18n\FrozenDate|null $due_on This date is in the user's timezone.
 * @property int $child_order
 * @property int $day_order
 * @property bool $evening
 * @property bool $completed
 * @property \Cake\I18n\FrozenTime|null $deleted_at
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property int $subtask_count
 * @property int $complete_subtask_count
 *
 * @property \App\Model\Entity\Project $project
 * @property \App\Model\Entity\Subtask[] $subtasks
 * @property \App\Model\Entity\Label[] $labels
 */
class Task extends Entity
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
        'project_id' => true,
        'section_id' => true,
        'title' => true,
        'body' => true,
        'due_on' => true,
        'completed' => true,
        'evening' => true,
        'subtasks' => true,
        'child_order' => false,
        'day_order' => false,
        'created' => false,
        'modified' => false,
        'deleted_at' => false,
        'project' => false,
        'labels' => false,
    ];

    public function complete(): void
    {
        $this->completed = true;
        $this->due_on = new FrozenDate();
    }

    public function incomplete(): void
    {
        $this->completed = false;
        $this->due_on = new FrozenDate();
    }

    public function softDelete(): void
    {
        $this->deleted_at = new FrozenTime();
    }

    public function undelete(): void
    {
        $this->deleted_at = null;
    }

    public function setDueOnFromString(?string $value)
    {
        if (!$value) {
            return;
        }
        try {
            $this->due_on = FrozenDate::parse($value);
        } catch (Exception $e) {
            $this->setError('due_on', 'Invalid date string.');
        }
    }

    public function removeTrailingEmptySubtask()
    {
        if (empty($this->subtasks)) {
            return;
        }
        $lastIndex = count($this->subtasks) - 1;
        if (isset($this->subtasks[$lastIndex]) && trim((string)$this->subtasks[$lastIndex]->title) === '') {
            unset($this->subtasks[$lastIndex]);
        }
    }
}
