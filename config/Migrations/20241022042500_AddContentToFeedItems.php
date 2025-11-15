<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddContentToFeedItems extends BaseMigration
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
        $table = $this->table('feed_items');
        $table->addColumn('content', 'text', [
            'default' => '',
        ]);
        $table->update();
    }
}
