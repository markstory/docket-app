<?php
declare(strict_types=1);

namespace App\View\Cell;

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
     * @var array<string, mixed>
     */
    protected $_validCellOptions = ['identity'];

    /**
     * @var \App\Model\Entity\User
     */
    protected $identity;

    /**
     * Initialization logic run at the end of object construction.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->Projects = $this->fetchTable('Projects');
    }

    /**
     * Default display method.
     *
     * @return void
     */
    public function display($identity)
    {
        $projects = $identity->applyScope('index', $this->Projects->find('active')->find('top'));
        $this->set('projects', $projects->all());
    }
}
