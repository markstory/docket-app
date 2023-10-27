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
 * @property \App\Model\Entity\Task[] $tasks
 * @property \App\Model\Entity\Label[] $labels
 * @property \App\Model\Entity\ProjectSection[] $sections
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
     * @var array<string, bool>
     */
    protected $_accessible = [
        'user_id' => false,
        'name' => true,
        'slug' => true,
        'color' => true,
        'favorite' => true,
        'archived' => true,
        'ranking' => false,
        'created' => false,
        'modified' => false,
        'user' => false,
        'tasks' => false,
        'labels' => false,
        'sections' => false,
    ];

    public function archive()
    {
        $this->archived = true;
    }

    public function unarchive()
    {
        $this->archived = false;
    }

    protected function _getColorHex(): string
    {
        $colors = $this->getColors();

        return $colors[$this->color]['code'];
    }

    public function getColors(): array
    {
        return [
            ['id' => 0, 'name' => 'green', 'code' => '#28aa48'],
            ['id' => 1, 'name' => 'teal', 'code' => '#6fd19d'],
            ['id' => 2, 'name' => 'plum', 'code' => '#5d3688'],
            ['id' => 3, 'name' => 'lavender', 'code' => '#b86fd1'],
            ['id' => 4, 'name' => 'sea blue', 'code' => '#218fa7'],
            ['id' => 5, 'name' => 'light blue', 'code' => '#78f0f6'],
            ['id' => 6, 'name' => 'toffee', 'code' => '#ab6709'],
            ['id' => 7, 'name' => 'peach', 'code' => '#fbaf45'],
            ['id' => 8, 'name' => 'berry', 'code' => '#a00085'],
            ['id' => 9, 'name' => 'pink', 'code' => '#fb4fc8'],
            ['id' => 10, 'name' => 'olive', 'code' => '#818c00'],
            ['id' => 11, 'name' => 'lime', 'code' => '#cef226'],
            ['id' => 12, 'name' => 'ultramarine', 'code' => '#4655ff'],
            ['id' => 13, 'name' => 'sky', 'code' => '#91b5ff'],
            ['id' => 14, 'name' => 'slate', 'code' => '#525876'],
            ['id' => 15, 'name' => 'smoke', 'code' => '#9197af'],
            ['id' => 16, 'name' => 'brick', 'code' => '#b60909'],
            ['id' => 17, 'name' => 'flame', 'code' => '#f14949'],
        ];
    }
}
