<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\User;
use Cake\View\Cell;

/**
 * ProjectsMenu cell
 */
class ProjectsMenuCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['identity'];

    /**
     * @var \App\Model\Entity\User
     */
    protected User $identity;

    /**
     * Default display method.
     *
     * @return void
     */
    public function display($identity): void
    {
        $projects = $this->fetchTable('Projects');

        $query = $identity->applyScope('index', $projects->find('active')->find('top'));
        $this->set('projects', $query->all());
    }
}
