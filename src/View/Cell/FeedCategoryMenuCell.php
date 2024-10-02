<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\User;
use Cake\View\Cell;

/**
 * FeedCategoryMenu cell
 */
class FeedCategoryMenuCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var array<string>
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
        $categories = $this->fetchTable('FeedCategories');

        $query = $identity->applyScope('index', $categories->find());
        $this->set('feedCategories', $query->all());
    }
}
