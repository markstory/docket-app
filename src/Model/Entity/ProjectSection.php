<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectSection Entity
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $ranking
 * @property bool $archived
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\Project $project
 * @property \App\Model\Entity\Task[] $tasks
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
     * @var array
     */
    protected $_accessible = [
        'project_id' => true,
        'name' => true,
        'ranking' => true,
        'archived' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'tasks' => true,
    ];
}
