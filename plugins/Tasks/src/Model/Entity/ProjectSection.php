<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectSection Entity
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $ranking
 * @property bool $archived
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Tasks\Model\Entity\Project $project
 * @property \Tasks\Model\Entity\Task[] $tasks
 */
class ProjectSection extends Entity
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
        'project_id' => true,
        'name' => true,
        'archived' => true,
        'ranking' => false,
        'created' => false,
        'modified' => false,
        'project' => false,
        'tasks' => false,
    ];

    public function archive(): void
    {
        $this->archived = true;
    }

    public function unarchive(): void
    {
        $this->archived = false;
    }
}
