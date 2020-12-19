<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Project Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property bool $favorite
 * @property bool $archived
 * @property int $ranking
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Task[] $todo_items
 * @property \App\Model\Entity\TodoLabel[] $todo_labels
 */
class Project extends Entity
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
        'user_id' => true,
        'name' => true,
        'slug' => true,
        'color' => true,
        'favorite' => true,
        'archived' => true,
        'ranking' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'todo_items' => true,
        'todo_labels' => true,
    ];

    public function archive()
    {
        $this->archived = true;
    }

    public function unarchive()
    {
        $this->archived = false;
    }
}
