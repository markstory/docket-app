<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddUnreadItemCountToFeedSubscriptions extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('feed_subscriptions');
        $table->addColumn('unread_item_count', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->update();

        $table = $this->table('feed_categories');
        $table->addColumn('unread_item_count', 'integer', [
            'default' => 0,
            'limit' => 11,
            'null' => false,
        ]);
        $table->update();
    }
}
