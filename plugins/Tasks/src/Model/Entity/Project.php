<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

use Cake\Core\Configure;
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
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \Tasks\Model\Entity\Task[] $tasks
 * @property \App\Model\Entity\Label[] $labels
 * @property \Tasks\Model\Entity\ProjectSection[] $sections
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
    protected array $_accessible = [
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

    public function archive(): void
    {
        $this->archived = true;
    }

    public function unarchive(): void
    {
        $this->archived = false;
    }

    protected function _getColorHex(): string
    {
        $colors = Configure::read('Colors');

        return $colors[$this->color]['code'];
    }
}
