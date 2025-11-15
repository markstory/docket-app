<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ExpandFeedItems extends BaseMigration
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
        $table
            ->addColumn('guid', 'string', [
                'null' => false,
            ])
            ->addColumn('url', 'string', [
                'null' => false,
            ])
            ->addIndex(['feed_id', 'guid'], ['unique' => true])
            ->save();
    }
}
