<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateFeedItemUserStates extends BaseMigration
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
        $table = $this->table('feed_item_users');
        $table->addColumn('user_id', 'integer', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('feed_item_id', 'integer', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('read_at', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('saved_at', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addForeignKey(['feed_item_id'], 'feed_items');
        $table->addForeignKey(['user_id'], 'users');
        $table->addIndex(['feed_item_id', 'user_id'], ['unique' => true]);
        $table->create();
    }
}
