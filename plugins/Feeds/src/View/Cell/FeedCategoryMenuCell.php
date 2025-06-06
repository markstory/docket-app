<?php
declare(strict_types=1);

namespace Feeds\View\Cell;

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
        $categories = $this->fetchTable('Feeds.FeedCategories');

        $query = $identity->applyScope('index', $categories->find('menu'));
        $this->set('feedCategories', $query->all());
    }
}
