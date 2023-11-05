<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

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
        'child_order' => false,
        'day_order' => false,
        'created' => false,
        'modified' => false,
        'deleted_at' => false,
        'project' => false,
        'subtasks' => false,
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

    public function getCompactDueOn(): string
    {
        if (!$this->due_on) {
            return '';
        }
        $delta = FrozenDate::today()->diffInDays($this->due_on, false);
        // In the past? Show the date.
        if ($delta < -90) {
            return $this->due_on->format('MMM d yyyy');
        }
        if ($delta < 0) {
            return $this->due_on->format('MMM d');
        }
        if ($delta < 1) {
            return 'Today';
        } else if ($delta < 2) {
            return 'Tomorrow';
        }
        if ($delta < 7) {
            return $this->due_on->format('iiii');
        }

        return $this->due_on->format('MMM d');
    }
}
